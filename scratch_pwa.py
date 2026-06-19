import os
import glob

PWA_TAGS = """
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0D0D0D">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="/manifest.json">
"""

def update_php_files():
    php_files = glob.glob('**/*.php', recursive=True)
    count = 0
    for file_path in php_files:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
            
        if '<head>' in content and 'manifest.json' not in content:
            # Replace existing viewport with PWA-friendly viewport
            if '<meta name="viewport"' in content:
                content = content.replace(
                    '<meta name="viewport" content="width=device-width, initial-scale=1.0">',
                    '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">'
                )
            
            # Insert PWA tags after <head>
            content = content.replace('<head>', f'<head>{PWA_TAGS}')
            
            # Make sure href to manifest is relative to root depending on depth
            depth = file_path.count(os.sep) + file_path.count('/')
            prefix = '../' * depth if depth > 0 else ''
            # Actually, using an absolute path `/manifest.json` might not work if it's not hosted at root.
            # Using relative path:
            relative_manifest = prefix + 'manifest.json'
            content = content.replace('href="/manifest.json"', f'href="{relative_manifest}"')
            
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(content)
            count += 1
            
    print(f"Updated {count} files with PWA tags.")

if __name__ == '__main__':
    update_php_files()
