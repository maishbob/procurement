import os, base64
base = "C:/laragon/www/procurement/resources/views/admin"

def w(relpath, content):
    fullpath = base + "/" + relpath
    os.makedirs(os.path.dirname(fullpath), exist_ok=True)
    open(fullpath, "w", encoding="utf-8").write(content)
    print("  OK:", relpath)

w('test_file.blade.php', base64.b64decode('QGV4dGVuZHMoJ2xheW91dHMuYXBwJykKQHNlY3Rpb24oJ3RpdGxlJywgJ3Rlc3QnKQo=').decode())
