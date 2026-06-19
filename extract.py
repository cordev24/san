import re
import sys

def extract_text(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Remove large base64 strings or images like data:image...
    content = re.sub(r'data:image/[^;]+;base64,[a-zA-Z0-9+/=]+', '[IMAGE_DATA]', content)
    
    return content

try:
    print("=== LEIVIS-PG2-BASE.MD ===")
    base_content = extract_text(r"C:\Users\Dell\Documents\san\.idea\leivis-pg2-base.md")
    print(base_content[-3000:])  # Let's print the last 3000 chars to see what's at the end
    
    print("\n=== SISTEMA.MD ===")
    sistema_content = extract_text(r"C:\Users\Dell\Documents\san\.idea\sistema.md")
    print(sistema_content[-3000:])
except Exception as e:
    print(e)
