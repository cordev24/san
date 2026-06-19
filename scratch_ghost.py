import os

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
        
    # Replace btn-outline with btn-ghost for all Cancel buttons
    # Since all the Cancel buttons in these files are now standard format after my previous script:
    content = content.replace('class="btn btn-outline" onclick="closeModal(\'nuevoGrupoModal\')">Cancelar</button>',
                              'class="btn btn-ghost" onclick="closeModal(\'nuevoGrupoModal\')">Cancelar</button>')
    
    with open(abs_path, 'w', encoding='utf-8') as f:
        f.write(content)

print("Done with ghost buttons")
