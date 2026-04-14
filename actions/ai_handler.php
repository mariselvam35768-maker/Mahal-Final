<?php
require_once '../includes/db.php';
require_once '../includes/config.php';
session_start();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$message = mb_strtolower($input['message'] ?? '', 'UTF-8');

if (empty($message)) {
    echo json_encode(['reply' => "Ask me anything, I'll answer!"]);
    exit;
}

// 0. Security Filter: Block technical/source code queries
$security_keywords = [
    'source code', 'folder structure', 'database schema', 'sql', 'query', 
    'file path', 'directory', 'structure', 'show code', 'admin/', 
    'includes/', 'actions/', '.php', 'config', 'password', 'credential'
];
foreach ($security_keywords as $kw) {
    if (mb_strpos($message, $kw) !== false) {
        echo json_encode(['reply' => "I apologize, but I am only authorized to assist with hall bookings and event inquiries. I cannot share technical system details or source code structure."]);
        exit;
    }
}

// --- Retrieval Engine ---
function retrieveProjectData($pdo) {
    try {
        $halls = $pdo->query("SELECT * FROM halls")->fetchAll(PDO::FETCH_ASSOC);
        $slots = $pdo->query("SELECT * FROM slots WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
        $today = date('Y-m-d');
        $next_month = date('Y-m-d', strtotime('+90 days'));
        $bookings_stmt = $pdo->prepare("SELECT b.*, h.name as hall_name, s.name as slot_name FROM bookings b JOIN halls h ON b.hall_id = h.id LEFT JOIN slots s ON b.slot_id = s.id WHERE b.event_date BETWEEN ? AND ? AND b.status = 'confirmed'");
        $bookings_stmt->execute([$today, $next_month]);
        $active_bookings = $bookings_stmt->fetchAll(PDO::FETCH_ASSOC);

        $locations = array_unique(array_column($halls, 'location'));
        $prices = array_column($halls, 'price_per_day');
        $min_price = !empty($prices) ? min($prices) : 0;
        
        return [
            'halls' => $halls,
            'slots' => $slots,
            'bookings' => $active_bookings,
            'meta' => [
                'count' => count($halls),
                'locations' => implode(", ", array_slice($locations, 0, 3)),
                'min_price' => $min_price
            ]
        ];
    } catch (Exception $e) {
        return ['halls' => [], 'slots' => [], 'bookings' => [], 'meta' => ['count' => 0, 'locations' => '', 'min_price' => 0]];
    }
}

$context = retrieveProjectData($pdo);
$reply = "";

// --- AUTO BOOKING ASSISTANT (CONVERSATIONAL STATE) ---
$booking_state = $_SESSION['ai_booking_state'] ?? 'idle';

// Global Greeting / Reset check
if (preg_match('/^(hi+|hello+|hai+|hey+|yo+|ola+)\b/u', $message)) {
    unset($_SESSION['ai_booking_state']);
    unset($_SESSION['ai_booking_data']);
    $booking_state = 'idle'; 
    $reply = "Hello! ✨ How can I help you today? I'm ready to find or book the perfect hall for Sri Lakshmi Residency & Mahal.";
}

// Global Reset exit
if (empty($reply) && preg_match('/(reset|restart|stop|cancel|cancel booking)/u', $message)) {
    unset($_SESSION['ai_booking_state']);
    unset($_SESSION['ai_booking_data']);
    $reply = "Sure, booking process cancelled. How else can I help you?";
}

// Start Booking intent
if (empty($reply) && $booking_state == 'idle' && preg_match('/(book|reserve)/u', $message)) {
    $_SESSION['ai_booking_state'] = 'waiting_for_hall';
    $hall_list = "";
    foreach(array_slice($context['halls'], 0, 3) as $h) { $hall_list .= "• {$h['name']}<br>"; }
    $reply = "Certainly! I'll help you book. Which hall would you like to reserve? <br>$hall_list";
}

// State: Waiting for Hall
elseif ($booking_state == 'waiting_for_hall') {
    $selected_hall = null;
    foreach ($context['halls'] as $hall) {
        if (mb_strpos($message, mb_strtolower($hall['name'], 'UTF-8'), 0, 'UTF-8') !== false) {
            $selected_hall = $hall;
            break;
        }
    }
    
    if ($selected_hall) {
        $_SESSION['ai_booking_data']['hall_id'] = $selected_hall['id'];
        $_SESSION['ai_booking_data']['hall_name'] = $selected_hall['name'];
        $_SESSION['ai_booking_state'] = 'waiting_for_date';
        $reply = "Great choice! <b>{$selected_hall['name']}</b>. What date are you planning for? (e.g., June 10)";
    } else {
        $reply = "I couldn't find that hall. Please pick one from our list.";
    }
}

// State: Waiting for Date
elseif ($booking_state == 'waiting_for_date') {
    $month_map = [
        'january' => '01', 'february' => '02', 'march' => '03', 'april' => '04', 'may' => '05', 'june' => '06',
        'july' => '07', 'august' => '08', 'september' => '09', 'october' => '10', 'november' => '11', 'december' => '12'
    ];
    $found_month = null; $found_day = null;
    foreach ($month_map as $name => $num) { if (mb_strpos($message, $name, 0, 'UTF-8') !== false) { $found_month = $num; break; } }
    if (preg_match('/(\d{1,2})[\/\-](\d{1,2})/', $message, $m)) { $found_day = $m[1]; $found_month = $m[2]; }
    elseif (preg_match('/(\d{1,2})/', $message, $m)) { $found_day = $m[1]; }

    if ($found_month && $found_day) {
        $day_str = str_pad($found_day, 2, '0', STR_PAD_LEFT);
        $query_date = date('Y') . "-$found_month-$day_str";
        
        $is_booked = false;
        foreach ($context['bookings'] as $booking) {
            if ($booking['hall_id'] == $_SESSION['ai_booking_data']['hall_id'] && $booking['event_date'] == $query_date) {
                $is_booked = true; 
                break;
            }
        }

        if (!$is_booked) {
            $_SESSION['ai_booking_data']['date'] = $query_date;
            $_SESSION['ai_booking_state'] = 'confirmation';
            $reply = "Perfect! <b>{$_SESSION['ai_booking_data']['hall_name']}</b> is free on $query_date. Say 'Confirm' to finalize the details.";
        } else {
            $reply = "Sorry, that hall is already booked for that date. Please pick another date.";
        }
    } else {
        $reply = "Please provide a valid date (e.g., June 25).";
    }
}

// State: Confirmation
elseif ($booking_state == 'confirmation') {
    if (preg_match('/(confirm|yes|ok)/u', $message)) {
        $h_id = $_SESSION['ai_booking_data']['hall_id'];
        $d = $_SESSION['ai_booking_data']['date'];
        unset($_SESSION['ai_booking_state']);
        unset($_SESSION['ai_booking_data']);
        $url = "halls.php?id=$h_id&date=$d";
        $reply = "Ready to book! <a href='$url' class='btn btn-primary' style='display:inline-block; margin-top:10px;'>Click here to complete your booking</a>";
    }
}

// --- NORMAL PARSING ---
if (empty($reply)) {
    // 1. Availability / Date Check
    if (preg_match('/(available|book|date)/u', $message) || preg_match('/\d+/', $message)) {
        $month_map = [
            'january' => '01', 'february' => '02', 'march' => '03', 'april' => '04', 'may' => '05', 'june' => '06',
            'july' => '07', 'august' => '08', 'september' => '09', 'october' => '10', 'november' => '11', 'december' => '12'
        ];
        $found_month = null; $found_day = null;
        
        foreach ($month_map as $name => $num) { 
            if (mb_strpos($message, $name, 0, 'UTF-8') !== false) { 
                $found_month = $num; 
                break; 
            } 
        }
        
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})/', $message, $m)) { 
            $found_day = $m[1]; 
            $found_month = $m[2]; 
        } elseif (preg_match('/(\d{1,2})/', $message, $m)) { 
            $found_day = $m[1]; 
            if (!$found_month && preg_match('/(available|book|date)/u', $message)) {
                $found_month = date('m');
            }
        }

        if ($found_month && $found_day) {
            $day_str = str_pad($found_day, 2, '0', STR_PAD_LEFT);
            $query_date = date('Y') . "-$found_month-$day_str";
            $available_info = [];
            
            foreach ($context['halls'] as $hall) {
                $hall_bookings = array_filter($context['bookings'], function($b) use ($hall, $query_date) {
                    return $b['hall_id'] == $hall['id'] && $b['event_date'] == $query_date;
                });
                
                $is_fully_booked = false;
                $booked_slots = [];
                foreach ($hall_bookings as $b) {
                    if ($b['is_full_day']) { $is_fully_booked = true; break; }
                    $booked_slots[] = $b['slot_id'];
                }
                
                if ($is_fully_booked) continue;
                
                $available_slots = [];
                foreach ($context['slots'] as $s) {
                    if (!in_array($s['id'], $booked_slots)) {
                        $available_slots[] = $s['name'];
                    }
                }
                
                if (!empty($available_slots)) {
                    $slot_text = count($available_slots) == count($context['slots']) ? "All Slots Free" : implode(", ", $available_slots) . " Available";
                    $available_info[] = "<b>{$hall['name']}</b> ($slot_text)";
                }
            }
            
            if (!empty($available_info)) {
                $h_list = implode("<br>", array_slice($available_info, 0, 3));
                $reply = "On $query_date, availability is as follows:<br>$h_list";
            } else { 
                $reply = "I'm sorry, all halls are fully booked for that date."; 
            }
        } else {
            // General month availability if only month mentioned
            if ($found_month && !$found_day) {
                 $free_days = [];
                 $days_in_month = cal_days_in_month(CAL_GREGORIAN, (int)$found_month, (int)date('Y'));
                 for ($d = 1; $d <= $days_in_month; $d++) {
                     if (count($free_days) >= 5) break;
                     $chk = date('Y') . "-$found_month-" . str_pad($d, 2, '0', STR_PAD_LEFT);
                     if ($chk < date('Y-m-d')) continue;
                     $is_free = true;
                     foreach ($context['bookings'] as $b) { 
                         if ($b['event_date'] == $chk) { 
                             $is_free = false; 
                             break; 
                         } 
                     }
                     if ($is_free) $free_days[] = $d;
                 }
                 if (!empty($free_days)) {
                     $days_list = implode(", ", $free_days);
                     $reply = "In that month, dates like $days_list are free.";
                 }
            }
        }
    }
}

// 2. Search / Recommendation
if (empty($reply) && preg_match('/(guests|capacity|people|recommend|area|location)/u', $message)) {
    preg_match('/\d+/', $message, $m);
    $guests = isset($m[0]) ? (int)$m[0] : 0;
    $best_matches = [];
    foreach ($context['halls'] as $hall) {
        $score = 0;
        if ($guests > 0 && $hall['capacity'] >= $guests) $score += 10;
        if (mb_strpos($message, mb_strtolower($hall['location'], 'UTF-8'), 0, 'UTF-8') !== false) $score += 20;
        if ($score > 0) $best_matches[] = ['name' => $hall['name'], 'loc' => $hall['location'], 'cap' => $hall['capacity'], 'score' => $score];
    }
    usort($best_matches, function($a, $b) { return $b['score'] - $a['score']; });
    if (!empty($best_matches)) {
        $reply = "Here are the best matches: <br>";
        foreach (array_slice($best_matches, 0, 2) as $match) { $reply .= "🏆 <b>{$match['name']}</b> ({$match['cap']} guests) in {$match['loc']}<br>"; }
    }
}

// 3. --- INTELLIGENCE FALLBACKS (Gemini & OpenAI) ---
if (empty($reply)) {
    // A. GOOGLE GEMINI FALLBACK
    if (defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY) && defined('USE_GEMINI') && USE_GEMINI) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . GEMINI_API_KEY;
        $systemContext = "You are the luxury Concierge for Sri Lakshmi Residency & Mahal. Respond EXCLUSIVELY in English.
                          Use this REAL-TIME system data:
                          HALLS: " . json_encode($context['halls']) . "
                          SLOTS: " . json_encode($context['slots']) . "
                          CONFIRMED BOOKINGS: " . json_encode($context['bookings']) . "
                          CURRENT DATE: " . date('Y-m-d') . "
                          
                          RULES:
                          1. Each day has multiple SLOTS (e.g., Morning, Evening).
                          2. A hall is available for a slot ONLY if no 'confirmed' booking exists for that hall+date+slot.
                          3. If 'is_full_day' is 1 for a hall+date, ALL slots are booked.
                          4. If only one slot is booked, tell the user the OTHER slot is still free.
                          5. STRICTLY RESPOND IN ENGLISH ONLY. Do not use Tamil or any other language.
                          6. SECURITY: NEVER share source code, file paths, folder structure, or database details. If asked, politely refuse.";
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => "Context: $systemContext\n\nUser Question: $message"]
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => 300,
                'temperature' => 0.7
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        if (!curl_errno($ch)) {
            $resData = json_decode($response, true);
            if (isset($resData['candidates'][0]['content']['parts'][0]['text'])) {
                $reply = $resData['candidates'][0]['content']['parts'][0]['text'];
            }
        }
        curl_close($ch);
    }
    
    // B. OPENAI FALLBACK
    if (empty($reply) && defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY) && defined('USE_OPENAI_FALLBACK') && USE_OPENAI_FALLBACK) {
        $url = 'https://api.openai.com/v1/chat/completions';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ];
        
        $systemPrompt = "You are the luxury AI Concierge for Sri Lakshmi Residency & Mahal. Respond ONLY in English. Use ONLY hall data. NEVER reveal source code, file paths, or system structure. System Data Summary: " . json_encode($context['meta']);
                         
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message]
            ],
            'max_tokens' => 200
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        if (!curl_errno($ch)) {
            $resData = json_decode($response, true);
            if (isset($resData['choices'][0]['message']['content'])) {
                $reply = $resData['choices'][0]['message']['content'];
            }
        }
        curl_close($ch);
    }
}

// 4. Default Project Fallback
if (empty($reply)) {
    $m = $context['meta'];
    $reply = "Here is what I know about our system: <br><br>";
    $reply .= "🏢 We have <b>{$m['count']}</b> halls.<br>";
    $reply .= "📍 Located in <b>{$m['locations']}</b>.<br>";
    $reply .= "💰 Starting at <b>Rs. " . number_format($m['min_price']) . "</b>/day.<br><br>";
    $reply .= "Say 'Book' to start!";
}

echo json_encode(['reply' => $reply]);
