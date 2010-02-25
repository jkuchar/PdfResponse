<?php

/**
 * My Application
 *
 * @copyright  Copyright (c) 2010 John Doe
 * @package    MyApplication
 */



/**
 * Homepage presenter.
 *
 * @author     John Doe
 * @package    MyApplication
 */
class HomepagePresenter extends BasePresenter
{
        public function createComponentForm($name){
                $form = new AppForm($this, $name);
                $form->addTextArea("html", "Upravte data", 80, 50)
                    ->controlPrototype->class[] = "tinymce";
                $form->addSubmit("doPDFka", "Do PDFka!");

                $form->setDefaults(
                    array(
                        "html" => $this->createTemplate()->setFile(APP_DIR."/templates/Homepage/pdf-source.phtml")->__toString()
                    )
                );

                $form->onSubmit[] = array($this,"onSubmit");
        }

        public function onSubmit(AppForm $form){
                $values = $form->values;

		// Create PDFResponse object
                $pdf = new PDFResponse($values["html"]);

		// Všechny tyto konfigurace jsou volitelné:
			
			// Orientace stránky
			$pdf->pageOrientaion = PDFResponse::ORIENTATION_LANDSCAPE;
			// Formát stránky
			$pdf->pageFormat = "A0";
			// Okraje stránky
			$pdf->pageMargins = "100,0,100,0,20,60";
			// Způsob zobrazení PDF
			$pdf->displayLayout = "continuous";
			// Velikost zobrazení
			$pdf->displayZoom = "fullwidth";
			// Název dokumentu
			$pdf->documentTitle = "Nadpis stránky";
			// Dokument vytvořil:
			$pdf->documentAuthor = "Jan Kuchař";

			// Callback - těsně před odesláním výstupu do prohlížeče
			//$pdfRes->onBeforeComplete[] = "test";

			$pdf->mPDF->IncludeJS("app.alert('This is alert box created by JavaScript in this PDF file!',3);");
			$pdf->mPDF->IncludeJS("app.alert('Now opening print dialog',1);");
			$pdf->mPDF->OpenPrintDialog();

		// Ukončíme presenter -> předáme řízení PDFresponse
                $this->terminate($pdf);
        }

}
