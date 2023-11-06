<?php
namespace App\Controller;

use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class MyPdfController extends AbstractController
{
    private $pdf;

    public function __construct(Pdf $pdf)
    {
        $this->pdf = $pdf;
    }

    public function generatePdfAction()
    {
        $html = $this->renderView('pdf/info_usuario_pdf.html.twig');
        
        $pdfContent = $this->pdf->getOutputFromHtml($html);

        return new Response(
            $pdfContent,
            200,
            array(
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="archivo.pdf"'
            )
        );
    }

    public function previewPdfAction()
    {
        $html = $this->renderView('pdf/info_usuario_pdf.html.twig');

        $pdfContent = $this->pdf->getOutputFromHtml($html);

        return $this->render('pdf/info_usuario_pdf.html.twig', [
            'pdfContent' => $pdfContent
        ]);
    }
}
