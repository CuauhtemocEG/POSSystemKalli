<?php
/**
 * Extensión de FPDF para soporte completo de UTF-8
 * Basado en la extensión tFPDF
 */

require_once BASE_PATH . 'fpdf/fpdf.php';

class UTF8_FPDF extends FPDF {
    
    protected function _escape($s) {
        // Convertir a ISO-8859-1 para FPDF
        return str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',utf8_decode($s))));
    }
    
    public function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
        // Convertir UTF-8 a ISO-8859-1
        $txt = $this->convertUTF8($txt);
        parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
    }
    
    public function Text($x, $y, $txt) {
        // Convertir UTF-8 a ISO-8859-1
        $txt = $this->convertUTF8($txt);
        parent::Text($x, $y, $txt);
    }
    
    public function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false) {
        // Convertir UTF-8 a ISO-8859-1
        $txt = $this->convertUTF8($txt);
        parent::MultiCell($w, $h, $txt, $border, $align, $fill);
    }
    
    private function convertUTF8($str) {
        // Tabla de conversión de caracteres UTF-8 a ISO-8859-1
        $utf8_iso = [
            // Letras con acentos
            'á' => chr(225), 'é' => chr(233), 'í' => chr(237), 'ó' => chr(243), 'ú' => chr(250),
            'Á' => chr(193), 'É' => chr(201), 'Í' => chr(205), 'Ó' => chr(211), 'Ú' => chr(218),
            'à' => chr(224), 'è' => chr(232), 'ì' => chr(236), 'ò' => chr(242), 'ù' => chr(249),
            'À' => chr(192), 'È' => chr(200), 'Ì' => chr(204), 'Ò' => chr(210), 'Ù' => chr(217),
            // Ñ española
            'ñ' => chr(241), 'Ñ' => chr(209),
            // Diéresis
            'ü' => chr(252), 'Ü' => chr(220),
            // Otros caracteres españoles
            '¿' => chr(191), '¡' => chr(161),
            '°' => chr(176), 'ç' => chr(231), 'Ç' => chr(199),
            // Caracteres de moneda
            '€' => chr(128), '£' => chr(163), '¥' => chr(165),
        ];
        
        return str_replace(array_keys($utf8_iso), array_values($utf8_iso), $str);
    }
}
?>
