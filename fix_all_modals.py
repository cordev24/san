import os
import re

modules_dir = r"c:\Users\Dell\Documents\san\modules"

# We want to match the wrapper and the two buttons in any order.
# Pattern 1: Submit first, then Cancel
pattern1 = re.compile(
    r'<div style="display:\s*flex;\s*gap:\s*var\(--space-4\);\s*margin-top:\s*var\(--space-6\);">\s*'
    r'<button type="submit" class="([^"]+)"\s*(?:style="flex:\s*1;"[^>]*)?>\s*'
    r'([\s\S]*?)</button>\s*'
    r'<button type="button" class="btn btn-outline" onclick="closeModal\(\'([^\']+)\'\)"(?:[^>]*)>\s*'
    r'Cancelar\s*</button>\s*</div>', re.MULTILINE
)

# Pattern 2: Cancel first, then Submit
pattern2 = re.compile(
    r'<div style="display:\s*flex;\s*gap:\s*var\(--space-4\);\s*margin-top:\s*var\(--space-6\);">\s*'
    r'<button type="button" class="btn btn-outline" onclick="closeModal\(\'([^\']+)\'\)"(?:[^>]*)>\s*'
    r'Cancelar\s*</button>\s*'
    r'<button type="submit" class="([^"]+)"\s*(?:style="flex:\s*1;"[^>]*)?>\s*'
    r'([\s\S]*?)</button>\s*</div>', re.MULTILINE
)

# Pattern 3: Similar to Pattern 1 but the cancel button has style="flex: 1;"
pattern3 = re.compile(
    r'<div style="display:\s*flex;\s*gap:\s*var\(--space-4\);\s*margin-top:\s*var\(--space-6\);">\s*'
    r'<button type="submit" class="([^"]+)"(?:[^>]*)>\s*'
    r'([\s\S]*?)</button>\s*'
    r'<button type="button" class="btn btn-outline"(?:[^>]*)onclick="closeModal\(\'([^\']+)\'\)"(?:[^>]*)>\s*'
    r'Cancelar\s*</button>\s*</div>', re.MULTILINE
)

# Pattern 4: Similar to Pattern 2 but flex:1 is somewhere else
pattern4 = re.compile(
    r'<div style="display:\s*flex;\s*gap:\s*var\(--space-4\);\s*margin-top:\s*var\(--space-6\);">\s*'
    r'<button type="button" class="btn btn-outline"(?:[^>]*)onclick="closeModal\(\'([^\']+)\'\)"(?:[^>]*)>\s*'
    r'Cancelar(?:.*?)</button>\s*'
    r'<button type="submit" class="([^"]+)"(?:[^>]*)>\s*'
    r'([\s\S]*?)</button>\s*</div>', re.MULTILINE
)

def repl(m, is_cancel_first=False):
    if not is_cancel_first:
        submit_class = m.group(1).replace('style="flex: 1;"', '').strip()
        submit_content = m.group(2)
        modal_name = m.group(3)
    else:
        modal_name = m.group(1)
        submit_class = m.group(2).replace('style="flex: 1;"', '').strip()
        submit_content = m.group(3)
        
    return (f'<div style="display: flex; justify-content: flex-end; gap: var(--space-4); margin-top: var(--space-6);">\n'
            f'                    <button type="button" class="btn btn-ghost" onclick="closeModal(\'{modal_name}\')">Cancelar</button>\n'
            f'                    <button type="submit" class="{submit_class}">{submit_content}</button>\n'
            f'                </div>')

files_modified = 0

for root, dirs, files in os.walk(modules_dir):
    for file in files:
        if file.endswith(".php"):
            path = os.path.join(root, file)
            with open(path, 'r', encoding='utf-8') as f:
                content = f.read()
                
            original_content = content
            
            # Apply replacements
            content = pattern1.sub(lambda m: repl(m, False), content)
            content = pattern2.sub(lambda m: repl(m, True), content)
            content = pattern3.sub(lambda m: repl(m, False), content)
            content = pattern4.sub(lambda m: repl(m, True), content)
            
            # Also catch any remaining button btn-outline that say Cancelar with style="flex: 1;"
            # We can just do a naive replace for those that are already in a justify-content: flex-end div
            
            if content != original_content:
                with open(path, 'w', encoding='utf-8') as f:
                    f.write(content)
                print(f"Updated {path}")
                files_modified += 1

print(f"Total files updated: {files_modified}")
