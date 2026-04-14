import os

path = r'c:\xampp\htdocs\Hall-Alocation\admin\manage_halls.php'
print(f"Opening {path}")

if not os.path.exists(path):
    print("File not found")
    exit(1)

with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()

new_lines = []
for line in lines:
    if 'Edit Hall' in line and '<h4' in line:
        # Replace the entire line with a clean version
        new_lines.append('<h4 style="margin:0;"><?php echo $action === "edit" ? "Edit Hall" : "Add New Hall"; ?></h4>\n')
    else:
        new_lines.append(line)

with open(path, 'w', encoding='utf-8') as f:
    f.writelines(new_lines)

print("File updated successfully")
