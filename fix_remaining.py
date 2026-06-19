import os
import re

modules_dir = r"c:\Users\Dell\Documents\san\modules"

files_modified = 0

for root, dirs, files in os.walk(modules_dir):
    for file in files:
        if file.endswith(".php"):
            path = os.path.join(root, file)
            with open(path, 'r', encoding='utf-8') as f:
                content = f.read()
                
            original_content = content
            
            # Simple approach: find div wrappers of these buttons
            # We look for <div style="..."> that contains Cancelar and another button
            # But the styles are arbitrary now (e.g. `style="display:flex; gap:10px; margin-top:20px;"`)
            
            # Let's target the exact blocks we found:
            # 1. usuarios/index.php block 1
            if "closeModal('crearModal')" in content and "btn-outline" in content:
                content = re.sub(
                    r'<div style="display:flex;\s*gap:10px;\s*margin-top:20px;">\s*<button type="submit" class="btn btn-violeta" style="flex:1;">([^<]+)</button>\s*<button type="button" class="btn btn-outline" onclick="closeModal\(\'crearModal\'\)" style="flex:1;">Cancelar</button>\s*</div>',
                    r'<div style="display: flex; justify-content: flex-end; gap: var(--space-4); margin-top: var(--space-6);">\n                    <button type="button" class="btn btn-ghost" onclick="closeModal(\'crearModal\')">Cancelar</button>\n                    <button type="submit" class="btn btn-violeta">\1</button>\n                </div>',
                    content
                )
            # 2. usuarios/index.php block 2
            if "closeModal('editarModal')" in content and "btn-outline" in content:
                content = re.sub(
                    r'<div style="display:flex;\s*gap:10px;\s*margin-top:20px;">\s*<button type="submit" class="btn btn-violeta" style="flex:1;">([^<]+)</button>\s*<button type="button" class="btn btn-outline" onclick="closeModal\(\'editarModal\'\)" style="flex:1;">Cancelar</button>\s*</div>',
                    r'<div style="display: flex; justify-content: flex-end; gap: var(--space-4); margin-top: var(--space-6);">\n                    <button type="button" class="btn btn-ghost" onclick="closeModal(\'editarModal\')">Cancelar</button>\n                    <button type="submit" class="btn btn-violeta">\1</button>\n                </div>',
                    content
                )
            
            # 3. pagos/verificacion.php
            if "closeRejectModal()" in content:
                content = re.sub(
                    r'<div style="display:flex; gap:10px;">\s*<button class="btn btn-violeta" style="flex:1;" onclick="confirmarRechazo\(\)">([^<]+)</button>\s*<button class="btn" style="flex:1;" onclick="closeRejectModal\(\)">Cancelar</button>\s*</div>',
                    r'<div style="display: flex; justify-content: flex-end; gap: var(--space-4); margin-top: var(--space-6);">\n                    <button type="button" class="btn btn-ghost" onclick="closeRejectModal()">Cancelar</button>\n                    <button type="button" class="btn btn-violeta" onclick="confirmarRechazo()">\1</button>\n                </div>',
                    content
                )
            
            # 4. categoria/grupo.php
            if "resetInscripcionForm()" in content:
                # There are two instances in grupo.php:
                # <div style="display:flex; gap:var(--space-2); margin-top:var(--space-4);">
                #     <button type="button" class="btn btn-outline" style="flex:1;" onclick="resetInscripcionForm()">Cancelar</button>
                #     <button type="submit" class="btn btn-menta" style="flex:1;">Inscribir</button>
                # </div>
                content = re.sub(
                    r'<div style="display:flex;\s*gap:var\(--space-2\);\s*margin-top:var\(--space-4\);">\s*<button type="button" class="btn btn-outline" style="flex:1;" onclick="resetInscripcionForm\(\)">Cancelar</button>\s*<button type="submit" class="([^"]+)" style="flex:1;">([^<]+)</button>\s*</div>',
                    r'<div style="display: flex; justify-content: flex-end; gap: var(--space-4); margin-top: var(--space-4);">\n                    <button type="button" class="btn btn-ghost" onclick="resetInscripcionForm()">Cancelar</button>\n                    <button type="submit" class="\1">\2</button>\n                </div>',
                    content
                )
                
            if content != original_content:
                with open(path, 'w', encoding='utf-8') as f:
                    f.write(content)
                print(f"Updated {path}")
                files_modified += 1

print(f"Done fixing flex:1. Total: {files_modified}")
