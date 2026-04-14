import re
import os

path = r'c:\xampp\htdocs\Hall-Alocation\admin\manage_halls.php'

if not os.path.exists(path):
    print("Error: File not found")
    exit(1)

with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

print(f"File size: {len(content)} characters")

# Very specific replacement for the header line
pattern = r'<h4 style="margin:0;">\s*<\?php echo \$action === \'edit\' \?.*?</h4>'
replacement = '<h4 style="margin:0;"><?php echo $action === \'edit\' ? \'Edit Hall\' : \'Add New Hall\'; ?></h4>'

new_content, count = re.subn(pattern, replacement, content, flags=re.DOTALL)

if count > 0:
    print(f"Successfully replaced {count} occurrences")
else:
    print("Regex failed, trying direct string search for substrings")
    # Fallback to lines
    lines = content.splitlines()
    new_lines = []
    found = False
    for line in lines:
        if '<h4' in line and '$action === \'edit\'' in line:
            new_lines.append('                    <h4 style="margin:0;"><?php echo $action === "edit" ? "Edit Hall" : "Add New Hall"; ?></h4>')
            found = True
        else:
            new_lines.append(line)
    
    if found:
        new_content = '\n'.join(new_lines)
        print("Fixed via string matching")
    else:
        print("All attempts failed")
        exit(1)

with open(path, 'w', encoding='utf-8') as f:
    f.write(new_content)

print("File write complete")
