import os

def fix_mojibake(directory):
    replacements = {
        'ÅŸ': 'ş',
        'Åž': 'Ş',
        'Ä±': 'ı',
        'Ä°': 'İ',
        'ÄŸ': 'ğ',
        'Äž': 'Ğ',
        'Ã¶': 'ö',
        'Ã–': 'Ö',
        'Ã¼': 'ü',
        'Ãœ': 'Ü',
        'Ã§': 'ç',
        'Ã‡': 'Ç',
        'â”€': '─'
    }

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
                print(f"Error in {filepath}: {e}")

    print(f"Total fixed files: {count}")

if __name__ == '__main__':
    directories_to_fix = [
        r'c:\Users\celal.karaman\Desktop\HEDEFLER\resources',
        r'c:\Users\celal.karaman\Desktop\HEDEFLER\app',
        r'c:\Users\celal.karaman\Desktop\HEDEFLER\routes',
    ]
    for directory in directories_to_fix:
        fix_mojibake(directory)
