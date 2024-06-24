<?php
namespace Module;
/**
 * Modul zur Erzeugung einer einfachen Excel-Tabelle mit fixierter Header-Zeile
 * Verwendet lib/classes/Excel.class.php
 *
 * Beispiel:
 *
 * $xls = $this->app->getModule("Excel");
 * $xls->setFilename("Text.xls");
 * $xls->setHeader(array("Name", "Vorname"));
 * $xls->addRow(array("Horst", "Schlemmer"));
 * $xls->addRow(array("Peter", "Lustig"));
 * $xls->addRow(array("Hans", "Dampf"));
 * $xls->getFile();
 *
 * @author Stefan
 */

class Excel extends \Module
{
    private $excel;

    public function __construct() {
        $this->excel = new \Excel();
    }

    /**
     * Definiert den Dateinamen der Tablle. Wenn die Funktion nicht aufgerufen wird, ist der Name "Tabelle.xls"
     *
     * @param String $name Dateiname
     */
    public function setFilename($name) {
        $this->excel->setFilename($name);
    }

    /**
     * Schreibt die Kopfzeile der Tabelle und fixiert sie.
     *
     * @param array $values Array mit den Namen der einzelnen Spalten
     */
    public function setHeader($values) {
        $this->excel->setHeader($values);
    }

    /**
     * Schreibt eine neue Zeile in die Tabelle
     *
     * @param Array $values Array mit den Werten der Zeile
     */
    public function addRow($values) {
        $this->excel->addRow($values);
    }

    /**
     * Schreibt eine neue Zelle in die Tabelle
     *
     * @param   int     $numRow     Nummer der Zeile
     * @param   int     $numCol     Nummer der Spalte
     * @param   string  $value      Inhalt der Zelle
     * @param   string  $format     Art der Formatierung:  "num"=numerisch mit 2 Nachkommastellen, "plz"=Zahl 5-stellig mir führender Null
     */
    public function addCell($numRow, $numCol, $value, $format = null) {
        $this->excel->addCell($numRow, $numCol, $value, $format);
    }

    /**
     * Sendet die Datei an den Browser.
     * Achtung: Anschließend ist keine weitere Aktion mehr möglich, da hier die Applikation mit die(); beendet wird.
     */
    public function getFile() {
        ob_end_clean();
        $this->excel->getFile();
        die();
    }
}
