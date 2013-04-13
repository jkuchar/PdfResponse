<?php

use Nette\InvalidStateException;
use Nette\Utils\Strings;

/**
 * PdfResponse
 * -----------
 * Wrapper of mPDF.
 * Generate PDF from Nette Framework in one line.
 *
 * @author     Jan Kuchař
 * @author     Tomáš Votruba
 * @author     Miroslav Paulík
 * @copyright  Copyright (c) 2010 Jan Kuchař (http://mujserver.net)
 * @version    2013, Nette 2.0.8 for PHP 5.3+ stable
 * @license    LGPL
 * @link       http://addons.nettephp.com/cs/pdfresponse
 */

/**
 * @property-read mPDF $mPDF
 */
class PdfResponse extends Nette\Object implements Nette\Application\IResponse
{

	const INLINE = "I";
	const DOWNLOAD = "D";

	/** @var string save mode */
	public $saveMode = self::DOWNLOAD;

	/**
	 * Source data
	 *
	 * @var mixed
	 */
	private $source;

	/** @var string path to (PDF) file */
	private $backgroundTemplate;

	/**
	 * Callback - create mPDF object
	 *
	 * @var Callback
	 */
	public $createMPDF = NULL;

	/**
	 * Portrait page orientation
	 */
	const ORIENTATION_PORTRAIT = "P";

	/**
	 * Landscape page orientation
	 */
	const ORIENTATION_LANDSCAPE = "L";

	/**
	 * Specifies page orientation.
	 * You can use constants:
	 * <ul>
	 *   <li>PdfResponse::ORIENTATION_PORTRAIT (default)
	 *   <li>PdfResponse::ORIENTATION_LANDSCAPE
	 * </ul>
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
	 * Please use values <b>higer than 0</b>. In some PDF browser zero values may
	 * cause problems!
	 *
	 * @var string
	 */
	public $pageMargins = "16,15,16,15,9,9";

	/**
	 * Author of the document
	 *
	 * @var string
	 */
	public $documentAuthor = "Nette Framework - Pdf response";

	/**
	 * Title of the document
	 *
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
	 *
	 * @var callback
	 */
	public $onBeforeComplete = array();

	/**
	 * Multi-language document?
	 *
	 * @var bool
	 */
	public $multiLanguage = FALSE;

	/**
	 * Additional stylesheet as a <b>string</b>
	 *
	 * @var string
	 */
	public $styles = "";

	/**
	 * <b>Ignore</b> styles in HTML document
	 * When using this feature, you MUST also install SimpleHTMLDom to your application!
	 *
	 * @var bool
	 */
	public $ignoreStylesInHTMLDocument = FALSE;

	/**
	 * mPDF instance
	 *
	 * @var mPDF
	 */
	private $mPDF = NULL;

	/**
	 * Generated pdf file
	 *
	 * @var mPDF
	 */
	private $generatedFile;

	/**
	 * @param mixed renderable variable
	 */
	public function __construct($source)
	{
		$this->createMPDF = callback($this, "createMPDF");
		$this->source = $source;
	}

	/**
	 * @param string $backgroundTemplate
	 */
	public function setBackgroundTemplate($pathToBackgroundTemplate)
	{
		if (!file_exists($pathToBackgroundTemplate)) {
			throw new \Nette\FileNotFoundException("File '$pathToBackgroundTemplate' not found.");
		}
		$this->backgroundTemplate = $pathToBackgroundTemplate;
	}

	/**
	 * Getts margins as array
	 *
	 * @return array
	 */
	function getMargins()
	{
		$margins = explode(",", $this->pageMargins);
		if (count($margins) !== 6) {
			throw new InvalidStateException("You must specify all margins! For example: 16,15,16,15,9,9");
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
		foreach ($margins AS $key => $val) {
			$val = (int)$val;
			if ($val < 0) {
				throw new InvalidArgumentException("Margin must not be negative number!");
			}
			$marginsOut[$dictionary[$key]] = $val;
		}

		return $marginsOut;
	}

	/**
	 * Getts source document
	 *
	 * @return mixed
	 */
	final public function getSource()
	{
		return $this->source;
	}

	/**
	 * Sends response to output
	 *
	 * @param Nette\Http\IRequest  $httpRequest
	 * @param Nette\Http\IResponse $httpResponse
	 * @return void
	 */
	public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse)
	{
		$mpdf = $this->build();
		$mpdf->Output(Strings::webalize($this->documentTitle), $this->saveMode);
	}

	/**
	 * Build final pdf
	 */
	private function build()
	{
		if (empty($this->documentTitle)) {
			throw new Exception("Var 'documentTitle' cannot be empty.");
		}

		if ($this->generatedFile) { // singleton
			return $this->generatedFile;
		}

		if ($this->backgroundTemplate) {
			// if background exists, then add it as a background
			$mpdf = $this->getMPDF();
			$mpdf->SetImportUse();
			$mpdf->AddPage();
			$pagecount = $mpdf->SetSourceFile($this->backgroundTemplate);
			$tplId = $mpdf->ImportPage($pagecount);
			$mpdf->UseTemplate($tplId);
		}

		if ($this->source instanceof Nette\Templating\ITemplate) {
			$this->source->pdfResponse = $this;
			$this->source->mPDF = $this->getMPDF();
			$html = $this->source->__toString();
		} else {
			$html = $this->source;
		}

		// Fix: $html can't be empty (mPDF generates Fatal error)
		if (empty($html)) {
			$html = "<html><body></body></html>";
		}

		$mpdf = $this->getMPDF();
		$mpdf->biDirectional = $this->multiLanguage;
		$mpdf->SetAuthor($this->documentAuthor);
		$mpdf->SetTitle($this->documentTitle);
		$mpdf->SetDisplayMode($this->displayZoom, $this->displayLayout);
		$mpdf->showImageErrors = TRUE;

		// @see: http://mpdf1.com/manual/index.php?tid=121&searchstring=writeHTML
		if ($this->ignoreStylesInHTMLDocument) {

			// copied from mPDF -> removes comments
			$html = preg_replace('/<!--mpdf/i', '', $html);
			$html = preg_replace('/mpdf-->/i', '', $html);
			$html = preg_replace('/<\!\-\-.*?\-\->/s', '', $html);

			// deletes all <style> tags
			$parsedHtml = new simple_html_dom($html);
			foreach ($parsedHtml->find("style") AS $el) {
				$el->outertext = "";
			}
			$html = $parsedHtml->__toString();

			$mode = 2; // If <body> tags are found, all html outside these tags are discarded, and the rest is parsed as content for the document. If no <body> tags are found, all html is parsed as content. Prior to mPDF 4.2 the default CSS was not parsed when using mode #2
		} else {
			$mode = 0; // Parse all: HTML + CSS
		}

		// Add content
		$mpdf->WriteHTML($html, $mode);

		// Add styles
		if (!empty($this->styles)) {
			$mpdf->WriteHTML($this->styles, 1);
		}

		$this->onBeforeComplete($mpdf);
		$this->generatedFile = $mpdf;

		return $this->generatedFile;
	}

	/**
	 * Returns mPDF object
	 *
	 * @return mPDF
	 */
	public function getMPDF()
	{
		if (!$this->mPDF instanceof mPDF) {
			if ($this->createMPDF instanceof Nette\Callback and $this->createMPDF->isCallable()) {
				$mpdf = $this->createMPDF->invoke($this);
				if (!($mpdf instanceof mPDF)) {
					throw new Nette\InvalidStateException("Callback function createMPDF must return mPDF object!");
				}
				$this->mPDF = $mpdf;
			} else {
				throw new Nette\InvalidStateException("Callback createMPDF is not callable or is not instance of Nette\\Callback!");
			}
		}

		return $this->mPDF;
	}

	/**
	 * Creates and returns mPDF object
	 *
	 * @internal param \PdfResponse $response
	 * @return mPDF
	 */
	public function createMPDF()
	{
		$margins = $this->getMargins();

		//  [ float $margin_header , float $margin_footer [, string $orientation ]]]]]])
		$mpdf = new mPDF('utf-8', // string $codepage
			$this->pageFormat, // mixed $format
			'', // float $default_font_size
			'', // string $default_font
			$margins["left"], // float $margin_left
			$margins["right"], // float $margin_right
			$margins["top"], // float $margin_top
			$margins["bottom"], // float $margin_bottom
			$margins["header"], // float $margin_header
			$margins["footer"], // float $margin_footer
			$this->pageOrientation);

		return $mpdf;
	}

	/**
	 * Save file to target location
	 *
	 * @param string
	 * @param string
	 * @return string
	 */
	public function save($location, $name = NULL)
	{
		$pdf = $this->build();
		$file = $pdf->output($name, "S");
		$name = Strings::webalize($name ? : $this->documentTitle) . ".pdf";

		file_put_contents($location . $name, $file);

		return $location . $name;
	}

	/**
	 * To force download, use PdfResponse::DOWNLOAD
	 * To show pdf in browser, use PdfResponse::INLINE
	 *
	 * @param string $saveMode
	 * @throws InvalidArgumentException
	 */
	public function setSaveMode($saveMode)
	{
		if (!in_array($saveMode, array(self::DOWNLOAD, self::INLINE))) {
			throw new \InvalidArgumentException("Invalid mode");
		}
		$this->saveMode = $saveMode;
	}

}
