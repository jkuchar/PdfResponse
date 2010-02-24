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

                $template = $this->createTemplate();
                $template->setFile(APP_DIR."/templates/Homepage/pdf-source.phtml");

                $form->setDefaults(
                    array(
                        "html"=>$template->__toString()
                    )
                );

                $form->onSubmit[] = array($this,"onSubmit");
        }

        public function onSubmit(AppForm $form){
                $vals = $form->values;
                /*$template = $this->createTemplate();
                $template->setFile(APP_DIR."/templates/pdf.phtml");*/
                $pdfRes = new PDFResponse($vals["html"]);
                $pdfRes->author = "Jan Kucha�";
                //$pdfRes->onBeforeComplete[] = "test";

                $mpdf = $pdfRes->mPDF;
                $mpdf; // Here you have full mPDF object
                $this->terminate($pdfRes);
        }

}
