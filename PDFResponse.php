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
use Nette\SmartObject;
use Nette\Utils\Strings;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Utils\Callback;

/**
 * @property-read mPDFExtended $mPDF
 */
class PdfResponse implements \Nette\Application\IResponse {

	use SmartObject;

	/**
	 * Source data
	 * @var mixed
	 */
	private $source;

	
	/**
	 * Callback - create mPDF object
	 * @var Callback
	 */
	public $createMPDF = null;

	
	/**
	 * Portrait page orientation
	 */
	const ORIENTATION_PORTRAIT  = "P";
	
	/**
	 * Landscape page orientation
	 */
	const ORIENTATION_LANDSCAPE = "L";

	/**
	 * Specifies page orientation.
	 *
	 * You can use constants:
	 * <ul>
	 *   <li>PdfResponse::ORIENTATION_PORTRAIT (default)
	 *   <li>PdfResponse::ORIENTATION_LANDSCAPE
	 * </ul>
	 *
	 * <b>In some usages this may not work.</b>
	 * If this setting does not work for you,
	 * you can specify page orientation by setting
	 * page size and orientation in style sheet of
	 * source document.
	 * <pre>
	 * @page { sheet-size: A4-L; }
	 * </pre>
	 *
	 * @var string
	 */
	public $pageOrientation = self::ORIENTATION_PORTRAIT;

	
	/**
	 * Specifies format of the document<br>
	 * <br>
	 * Allowed values: (Values are case-<b>in</b>sensitive)
	 * <ul>
	 *   <li>A0 - A10
	 *   <li>B0 - B10
	 *   <li>C0 - C10
	 *   <li>4A0
	 *   <li>2A0
	 *   <li>RA0 - RA4
	 *   <li>SRA0 - SRA4
	 *   <li>Letter
	 *   <li>Legal
	 *   <li>Executive
	 *   <li>Folio
	 *   <li>Demy
	 *   <li>Royal
	 *   <li>A<i> (Type A paperback 111x178mm)</i>
	 *   <li>B<i> (Type B paperback 128x198mm)</i>
	 * </ul>
	 *
	 * @var string
	 */
	public $pageFormat = "A4";

	/**
	 * Margins in this order:
	 * <ol>
	 *   <li>top
	 *   <li>right
	 *   <li>bottom
	 *   <li>left
	 *   <li>header
	 *   <li>footer
	 * </ol>
	 *
	 * Please use values <b>higer than 0</b>. In some PDF browser zero values may
	 * cause problems!
	 *
	 * @var string
	 */
	public $pageMargins = "16,15,16,15,9,9";

	/**
	 * Author of the document
	 * @var string
	 */
	public $documentAuthor = "Nette Framework - Pdf response";

	/**
	 * Title of the document
	 * @var string
	 */
	public $documentTitle = "Unnamed document";

	/**
	 * This parameter specifies the magnification (zoom) of the display when the document is opened.<br>
	 * Values (case-<b>sensitive</b>)
	 * <ul>
	 *   <li><b>fullpage</b>: Fit a whole page in the screen
	 *   <li><b>fullwidth</b>: Fit the width of the page in the screen
	 *   <li><b>real</b>: Display at real size
	 *   <li><b>default</b>: User's default setting in Adobe Reader
	 *   <li><i>integer</i>: Display at a percentage zoom (e.g. 90 will display at 90% zoom)
	 * </ul>
	 * 
	 * @var string|int
	 */
	public $displayZoom = "default";

	/**
	 * Specify the page layout to be used when the document is opened.<br>
	 * Values (case-<b>sensitive</b>)
	 * <ul>   
	 *   <li><b>single</b>: Display one page at a time
	 *   <li><b>continuous</b>: Display the pages in one column
	 *   <li><b>two</b>: Display the pages in two columns
	 *   <li><b>default</b>: User's default setting in Adobe Reader
	 * </ul>
	 * 
	 * @var string
	 */
	public $displayLayout = "continuous";

	/**
	 * Before document output starts
	 * @var callback
	 */
	public $onBeforeComplete = array();

	/**
	 * Before document write starts
	 * @var callback
	 */
	public $onBeforeWrite = array();

	/**
	 * Multi-language document?
	 * @var bool
	 */
	public $multiLanguage = false;

	/**
	 * Additional stylesheet as a <b>string</b>
	 * @var string
	 */
	public $styles = "";

	/**
	 * <b>Ignore</b> styles in HTML document
	 * When using this feature, you MUST also install SimpleHTMLDom to your application!
	 * @var bool
	 */
	public $ignoreStylesInHTMLDocument = false;

	/**
	 * mPDFExtended instance
	 * @var mPDFExtended
	 */
	private $mPDF = null;

	/**
	 * Document name on output
	 * @var string
	 */
	public $outputName = null;

	/**
	 * send the file inline to the browser. The plug-in is used if available. The name given by filename is used when one selects the "Save as" option on the link generating the PDF.
	 */
	const OUTPUT_INLINE = "I";

	/**
	 * send to the browser and force a file download with the name given by filename.
	 */
	const OUTPUT_DOWNLOAD = "D";

	/**
	 * save to a local file with the name given by filename (may include a path).
	 */
	const OUTPUT_FILE = "F";

	/**
	 * return the document as a string. filename is ignored.
	 */
	const OUTPUT_STRING = "S";

	/**
	 * Output destination
	 * @var string
	 */
	public $outputDestination = self::OUTPUT_INLINE;

	/**
	 * Getts margins as <b>array</b>
	 * @return array
	 */
	function getMargins() {
		$margins = explode(",", $this->pageMargins);
		if(count($margins) !== 6) {
			throw new \Nette\InvalidStateException("You must specify all margins! For example: 16,15,16,15,9,9");
		}

		$dictionary = array(
			0 => "top",
			1 => "right",
			2 => "bottom",
			3 => "left",
			4 => "header",
			5 => "footer"
		);

		$marginsOut = array();
		foreach($margins AS $key => $val) {
			$val = (int)$val;
			if($val < 0) {
				throw new \Nette\InvalidArgumentException("Margin must not be negative number!");
			}
			$marginsOut[$dictionary[$key]] = $val;
		}

		return $marginsOut;
	}

	/**
	 * PdfResponse constructor.
	 * @param $source
	 */
	public function __construct($source) {
		$this->createMPDF = array($this, "createMPDF");
		$this->source = $source;
	}



	/**
	 * Getts source document html
	 * @return string
	 * @throws \Nette\InvalidStateException
	 */
	public function getSource() {
		$source = $this->getRawSource();

		// String given
		if(is_string($source)) {
			return $source;
		};

		// Nette template given
		if ($source instanceof \Nette\Templating\ITemplate || $source instanceof \Nette\Application\UI\ITemplate ) {
			$source->pdfResponse = $this;
			$source->mPDF = $this->getMPDF();
			return $source->__toString();

		};

		// Other case - not supported
		throw new \Nette\InvalidStateException("Source is not supported! (type: ".
			(is_object($source) ? ("object of class " . get_class($source)) : gettype($source)).
		")");
	}

	public function getRawSource() {
		if(!$this->source) {
			throw new \Nette\InvalidStateException("Source is not defined!");
		}
		
		return $this->source;
	}



	/**
	 * Sends response to output.
	 * @return void
	 */
	public function send(IRequest $httpRequest, IResponse $httpResponse) {
		// Throws exception if sources can not be processed
		$html = $this->getSource();
		
		// Fix: $html can't be empty (mPDF generates Fatal error)
		if(empty($html)) {
			$html = "<html><body></body></html>";
		}

		$mpdf = $this->getMPDF();
		$mpdf->biDirectional = $this->multiLanguage;
		$mpdf->SetAuthor($this->documentAuthor);
		$mpdf->SetTitle($this->documentTitle);
		$mpdf->SetDisplayMode($this->displayZoom,$this->displayLayout);

		// @see: http://mpdf1.com/manual/index.php?tid=121&searchstring=writeHTML
		if($this->ignoreStylesInHTMLDocument) {

			// copied from mPDF -> removes comments
			$html = preg_replace('/<!--mpdf/i','',$html);
			$html = preg_replace('/mpdf-->/i','',$html);
			$html = preg_replace('/<\!\-\-.*?\-\->/s','',$html);

			// deletes all <style> tags
			$parsedHtml = new simple_html_dom($html);
			foreach($parsedHtml->find("style") AS $el) {
				$el->outertext = "";
			}
			$html = $parsedHtml->__toString();

			$mode = 2; // If <body> tags are found, all html outside these tags are discarded, and the rest is parsed as content for the document. If no <body> tags are found, all html is parsed as content. Prior to mPDF 4.2 the default CSS was not parsed when using mode #2
		}else {
			$mode = 0; // Parse all: HTML + CSS
		}


		if(class_exists('\\simple_html_dom',true)) {
			// Support for base64 encoded images - workaround
			$parsedHtml = new \simple_html_dom($html);
			$i = 1000;
			foreach($parsedHtml->find("img") AS $element) {
				$boundary1 = "data:";
				$pos1 = strlen($boundary1);
				if(!substr($element->src,0,$pos1) == $boundary1) continue;
				$pos2 = strpos($element->src,";",$pos1);
				if($pos2 === false) continue;
				$mime = substr($element->src, $pos1, $pos2-$pos1);
				$boundary = "base64,";
				$base64 = substr($element->src, $pos2+strlen($boundary)+1);
				
				$data = base64_decode($base64);
				if($data === false) continue;
				
				$propertyName = "base64Image".$i;
				$mpdf->$propertyName = $data;
				$element->src = "var:".$propertyName;
				$i++;
			}
			$html = $parsedHtml->__toString();
		}

		$this->onBeforeWrite($mpdf);

		// Add content
		$mpdf->WriteHTML(
			$html,
			$mode
		);

		// Add styles
		if(!empty($this->styles)) {
			$mpdf->WriteHTML(
				$this->styles,
				1
			);
		}

		$this->onBeforeComplete($mpdf);

		if(!$this->outputName) {
			$this->outputName = Strings::webalize($this->documentTitle).".pdf";
		}

		$mpdf->Output($this->outputName,$this->outputDestination);
	}


	/**
	 * Returns mPDF object
	 * @return mPDFExtended
	 */
	public function getMPDF() {
		if(!$this->mPDF instanceof Mpdf) {
			if(Callback::check($this->createMPDF)) {
				$mpdf = Callback::invoke($this->createMPDF);
				if(!($mpdf instanceof Mpdf)) {
					throw new \Nette\InvalidStateException("Callback function createMPDF must return mPDF object!");
				}
				$this->mPDF = $mpdf;
			}else
				throw new \Nette\InvalidStateException("Callback createMPDF is not callable!");
		}
		return $this->mPDF;
	}


	/**
	 * Creates and returns mPDF object
	 * @return mPDFExtended
	 */
	public function createMPDF() {
		$margins = $this->getMargins();

		$mpdf = new mPDFExtended([
			'mode' => 'utf-8',
			'format' => $this->pageFormat,
			'default_font_size' => '',
			'default_font' => '',
			'margin_left' => $margins["left"],
			'margin_right' => $margins["right"],
			'margin_top' => $margins["top"],
			'margin_bottom' => $margins["bottom"],
			'margin_header' => $margins["header"],
			'margin_footer' => $margins["footer"],
			'orientation' => $this->pageOrientation,
		]);

		return $mpdf;
	}
}