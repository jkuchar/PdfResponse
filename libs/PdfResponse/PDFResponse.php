<?php

/**
 * @property-read mPDFExtended $mPDF
 */
class PDFResponse extends Object implements IPresenterResponse
{
        /**
         * path to mPDF.php
         * @var string
         */
	public static $mPDFPath = "%libsDir%/PdfResponse/mpdf/mpdf.php";

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

        const ORIENTATION_PORTRAIT  = "P";
        const ORIENTATION_LANDSCAPE = "L";

        /**
         * Specifies page orientation.
         * You can use constants:<br>
         *   ORIENTATION_PORTRAIT (default)<br>
         *   ORIENTATION_LANDSCAPE
         *
         * @var string
         */
        public $pageOrientaion = self::ORIENTATION_PORTRAIT;

        /**
         * Specifies format of the document<br>
         * Allowed values:<br>
         *   Values (case-insensitive)<br>
         *   A0 - A10<br>
         *   B0 - B10<br>
         *   C0 - C10<br>
         *   4A0<br>
         *   2A0<br>
         *   RA0 - RA4<br>
         *   SRA0 - SRA4<br>
         *   Letter<br>
         *   Legal<br>
         *   Executive<br>
         *   Folio<br>
         *   Demy<br>
         *   Royal<br>
         *   A (Type A paperback 111x178mm)<br>
         *   B (Type B paperback 128x198mm)<br>
         *
         * @var string
         */
        public $pageFormat = "A4";

        /**
         * Margins in this order:<br>
         *   top<br>
         *   right<br>
         *   bottom<br>
         *   left<br>
         *   header<br>
         *   footer<br>
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
         * Values (case-sensitive)<br>
         *   fullpage: Fit a whole page in the screen<br>
         *   fullwidth: Fit the width of the page in the screen<br>
         *   real: Display at real size<br>
         *   default: User's default setting in Adobe Reader<br>
         *   INTEGER : Display at a percentage zoom (e.g. 90 will display at 90% zoom)<br>
         *
         * @var string|int
         */
        public $displayZoom = "default";

        /**
         * Specify the page layout to be used when the document is opened.<br>
         * Values (case-sensitive)<br>
         *   single: Display one page at a time<br>
         *   continuous: Display the pages in one column<br>
         *   two: Display the pages in two columns<br>
         *   default: User's default setting in Adobe Reader<br>
         * @var string
         */
        public $displayLayout = "continuous";

        /**
         * Nette Callbacks
         * @var array
         */
        public $onBeforeComplete = array();

	/**
	 * Multi-language document
	 * @var bool
	 */
	public $multiLanguage = false;

	/**
	 * Add this style sheet to the document (CSS)
	 * @var string
	 */
	public $styles = "";

	/**
	 * Ignore styles in HTML document
	 * When using this feature, you MUST also install SimpleHTMLDom to your application!
	 * @var bool
	 */
	public $ignoreStylesInHTMLDocument = false;

	/**
	 * mPDFExtended
	 * @var mPDFExtended
	 */
        private $mPDF = null;

        function getMargins(){
            $margins = explode(",", $this->pageMargins);
            if(count($margins) !== 6) {
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
            foreach($margins AS $key => $val){
                $val = (int)$val;
                if($val < 0) {
                    throw new InvalidArgumentException("Margin must not be negative number!");
                }
                $marginsOut[$dictionary[$key]] = $val;
            }
            
            return $marginsOut;
        }

	/**
	 * @param  mixed  renderable variable
	 */
	public function __construct($source)
	{
                $this->createMPDF = callback($this,"createMPDF");
		$this->source = $source;
	}



	/**
	 * @return mixed
	 */
	final public function getSource()
	{
		return $this->source;
	}



	/**
	 * Sends response to output.
	 * @return void
	 */
	public function send()
	{
		if ($this->source instanceof ITemplate) {
			$html = $this->source->__toString();

		} else {
			$html = $this->source;
		}

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
		}else{
			$mode = 0; // Parse all: HTML + CSS
		}

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

                $mpdf->Output(String::webalize($this->documentTitle),'I');
	}


        /**
         * Returns mPDF object
         * @return mPDFExtended
         */
        public function getMPDF(){
                if(!$this->mPDF instanceof mPDF) {
			if($this->createMPDF instanceof Callback and $this->createMPDF->isCallable()){
				$mpdf = $this->createMPDF->invoke($this);
				if(!($mpdf instanceof mPDF)) {
				    throw new InvalidStateException("Callback function createMPDF must return mPDF object!");
				}
				$this->mPDF = $mpdf;
			}else
				throw new InvalidStateException("Callback createMPDF is not callable or is not instance of Nette\Callback!");
                }
                return $this->mPDF;
        }



        /**
         * Creates and returns mPDF object
         * @param PDFResponse $response
         * @return mPDFExtended
         */
        public function createMPDF(){
		/*if(!self::$mPDFPath) {
			self::$mPDFPath = dirname(__FILE__)."/mpdf/mpdf.php";
		}*/
                $mpdfPath = Environment::expand(self::$mPDFPath);
                define('_MPDF_PATH',dirname($mpdfPath)."/");
                require($mpdfPath);

                $margins = $this->getMargins();

                //  [ float $margin_header , float $margin_footer [, string $orientation ]]]]]])
                $mpdf = new mPDFExtended(
                    'utf-8',            // string $codepage
                    $this->pageFormat,  // mixed $format
                    '',                 // float $default_font_size
                    '',                 // string $default_font
                    $margins["left"],   // float $margin_left
                    $margins["right"],  // float $margin_right
                    $margins["top"],    // float $margin_top
                    $margins["bottom"], // float $margin_bottom
                    $margins["header"], // float $margin_header
                    $margins["footer"], // float $margin_footer
                    $this->pageOrientaion
                );

                return $mpdf;
        }

}