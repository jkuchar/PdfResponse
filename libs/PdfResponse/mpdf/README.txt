Upgrading
============

To upgrade from mPDF 5.3 to 5.4, simply upload all the files to their corresponding folders, overwriting files as required.
If you wish to keep your config.php file, you will need to make the following edits (using the new config.php as reference):

Add the two new variables:
$this->bookmarkStyles = array();
$this->cacheTables = false;

Edit the following 3 variables:
$this->allowedCSStags
$this->innerblocktags
$this->defaultCSS
(In all cases, the addition is related to CAPTION)

Finally remove the $this->form_* variables
(These have all been relocated in /classes/form.php)
