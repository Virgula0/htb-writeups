#!/usr/bin/python
import zipfile
from io import BytesIO

def create_zip():
    f = BytesIO()
    z = zipfile.ZipFile(f, 'w', zipfile.ZIP_DEFLATED)
    z.writestr('test.php%00.pdf', 'Content of the file') # this won't work because we need a pdf extensions
    z.close()
    zip = open('test.zip','wb') 
    zip.write(f.getvalue())
    zip.close() 

create_zip()
