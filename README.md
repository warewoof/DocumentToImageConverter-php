# doctoimageconverter-php

This PHP script is a functioning proof of concept for converting MS documents (doc/docx, xls/xlsx, ppt/pptx) and PDF into image format (jpg, png, gif, etc). 

These are the steps in order to run it on a Mac system:

1. Install [LibreOffice](http://download.documentfoundation.org/libreoffice/stable/4.4.4/mac/x86_64/LibreOffice_4.4.4_MacOS_x86-64.dmg)

2. Install [ImageMagick](http://cactuslab.com/imagemagick/assets/ImageMagick-6.9.1-0.pkg.zip)

3. Install [Ghostscript](http://cactuslab.com/imagemagick/assets/Ghostscript-9.07.pkg.zip)

4. Update the 3 path variables at the beginning of the script to point correspond to the install paths of the above three programs

5. Run

Additional notes:
* Any running instances of LibreOffice should be closed before running the script

* Image density can be changed, but will directly affect performance. At 150, it seems to average just under 1 second per page conversion with reasonable quality, but this will be variable depending on system spec and loading

* The image display is using FancyBox library, which is customized with the addition of First/Last buttons and Next/Previous without needing to resize.

