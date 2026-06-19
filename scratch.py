import os
import re

files_to_update = [
    "modules/categoria/index.php",
    "modules/electrodomesticos/index.php",
    "modules/grupos/index.php",
    "modules/motocicletas/index.php",
    "modules/telefonia/index.php"
]

for file_path in files_to_update:
    abs_path = os.path.join(r"c:\Users\Dell\Documents\san", file_path)
    if not os.path.exists(abs_path):
        continue
        
    with open(abs_path, 'r', encoding='utf-8') as f:
        content = f.read()
        
    # Replace the wrapper div
    content = content.replace('<div style="display: flex; gap: var(--space-4); margin-top: var(--space-6);">', '<div style="display: flex; justify-content: flex-end; gap: var(--space-4); margin-top: var(--space-6);">')
    
    # We need to swap the button order and remove `style="flex: 1;"`
    # Let's do it with a regex for the btn groups. We look for the div wrapper and the buttons inside.
    # Actually, a simpler way is just to manually replace the known strings.
    # Because there are variations in SVG icons, I'll use regex.
    
    pattern = re.compile(
        r'<div style="display: flex; justify-content: flex-end; gap: var\(--space-4\); margin-top: var\(--space-6\);">\s*'
        r'<button type="submit" class="([^"]+)" style="flex: 1;">\s*'
        r'([\s\S]*?)</button>\s*'
        r'<button type="button" class="btn btn-outline" onclick="closeModal\(\'([^\']+)\'\)"(?:[\s\n]*)style="flex: 1;">\s*'
        r'Cancelar\s*</button>\s*</div>', re.MULTILINE
    )
    
    def repl(m):
        submit_class = m.group(1)
        submit_content = m.group(2)
        modal_name = m.group(3)
        
        return (f'<div style="display: flex; justify-content: flex-end; gap: var(--space-4); margin-top: var(--space-6);">\n'
                f'                    <button type="button" class="btn btn-outline" onclick="closeModal(\'{modal_name}\')">Cancelar</button>\n'
                f'                    <button type="submit" class="{submit_class}">{submit_content}</button>\n'
                f'                </div>')
                
    content = pattern.sub(repl, content)
    
    # Wait, some cancel buttons are single-line like: <button type="button" class="btn btn-outline" onclick="closeModal('nuevoGrupoModal')" style="flex: 1;">Cancelar</button>
    # The regex handles it if there is whitespace.
    
    with open(abs_path, 'w', encoding='utf-8') as f:
        f.write(content)
print("Done")
