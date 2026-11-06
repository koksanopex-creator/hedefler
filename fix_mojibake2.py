import os

def generate_replacements():
    replacements = {}
    turkish_chars = 'çÇğĞıİöÖşŞüÜ'
    for char in turkish_chars:
        # Encode to UTF-8 and decode as latin1 to simulate the mojibake
        mojibake = char.encode('utf-8').decode('latin1')
        replacements[mojibake] = char
    
    # Also add windows-1252 to utf-8 just in case it was 1252 instead of latin1
    for char in turkish_chars:
        try:
            mojibake = char.encode('utf-8').decode('windows-1252')
            if mojibake not in replacements:
                replacements[mojibake] = char
        except UnicodeDecodeError:
            pass

    # Manually add some known ones
    replacements['â”€'] = '─'
    return replacements

def fix_mojibake(directory):
    replacements = generate_replacements()
    # Sort replacements by length descending to replace longer sequences first
    replacements = dict(sorted(replacements.items(), key=lambda item: len(item[0]), reverse=True))

    count = 0
    for root, _, files in os.walk(directory):
        for file in files:
            if not file.endswith('.php') and not file.endswith('.html') and not file.endswith('.js') and not file.endswith('.json'):
                continue
                
            filepath = os.path.join(root, file)
            
            try:
                with open(filepath, 'r', encoding='utf-8') as f:
                    content = f.read()
                    
                new_content = content
                for wrong, right in replacements.items():
                    new_content = new_content.replace(wrong, right)
                    
                if new_content != content:
                    with open(filepath, 'w', encoding='utf-8') as f:
                        f.write(new_content)
                    count += 1
                    print(f"Fixed {filepath}")
            except Exception as e:
                pass # skip files that can't be read as utf-8

    print(f"Total fixed files in {directory}: {count}")

if __name__ == '__main__':
    directories_to_fix = [
        r'c:\Users\celal.karaman\Desktop\HEDEFLER\resources\views',
        r'c:\Users\celal.karaman\Desktop\HEDEFLER\app',
        r'c:\Users\celal.karaman\Desktop\HEDEFLER\routes',
        r'c:\Users\celal.karaman\Desktop\HEDEFLER\lang',
    ]
    for directory in directories_to_fix:
        if os.path.exists(directory):
            fix_mojibake(directory)
