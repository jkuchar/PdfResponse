<?php

/**
 * PdfResponse
 * -----------
 * Wrapper of mPDF.
 * Generate PDF from Nette Framework in one line.
 *
 * @author     Jan Kuchař
 * @copyright  Copyright (c) 2010 Jan Kuchař (http://mujserver.net)
 * @license    LGPL
 * @link       http://addons.nettephp.com/cs/pdfresponse
 */

namespace PdfResponse;

use Mpdf\Mpdf;

/**
 * Extended version of mPDF
 *  - added support for JavaScript
 *  - shortcut for opening print dialog
 */
class mPDFExtended extends Mpdf {

	function OpenPrintDialog() {
		$this->SetJS('print()');
	}

}
