<?php
require_once '../config/database.php';
require_once '../lib/utils.php';

$pdo = getConnection();

// Validar parámetro
if (!isset($_GET['fecha'])) {
    jsonResponse(['error' => 'Parámetro "fecha" requerido'], 400);
}

$fecha = $_GET['fecha'];

// Validar formato de fecha YYYY-MM-DD
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    jsonResponse(['error' => 'Formato de fecha inválido. Use YYYY-MM-DD'], 400);
}

// Consulta con filtro por datamodificacio
$sql = <<<SQL
SELECT DISTINCT ON (ia.idinf)
  ia.expcontratacio AS codigo_expediente,
  c.codi AS codigo_centro,
  pi.objecte AS descripcion_larga,
  ia.estat AS estado,
  ia.dataaprovacio AS fecha_autorizado,
  pi.plic AS importe_pa,
  e.dataadjudicacio AS fecha_adjudicacion,
  eo.plic AS importe_adjudicacion,
  e.dataperfilcontratant AS fecha_publicacion,
  pi.termini AS plazo_ejecucion,
  eo.maillicitacio AS email,
  e.datainiciexecucio AS fecha_inicio,
  e.datafigarantia AS fecha_fin_garantia,
  e.dataliquidacio AS fecha_liquidacion,
  e.datalimitprsentacio AS fecha_limite_ofertas,
  e.datarecepcio AS fecha_recepcion,
  e.dataretorngarantia AS fecha_retorno_garantia,
  pe.funcio AS responsable,
  e.tipus AS tipo_contrato,
  pi.tipusllicencia AS autorizacion_urbanistica,
  pi.contracte AS formalizacion_contrato,
  --em.nom || ' - ' || em.cif AS empresa,
  e.dataformalitzaciocontracte AS fecha_formalizacion,
  e.dataliquidacio AS fecha_liquidacion_contrato,
  e.dataficontracte AS fecha_cierre,
  a.datamodificacio AS fecha_modificacion
FROM tbl_informeactuacio ia
JOIN tbl_actuacio a ON ia.idactuacio = a.id
JOIN tbl_propostesinforme pi ON pi.idinf = ia.idinf
JOIN tbl_expedient e ON ia.expcontratacio = e.expcontratacio
LEFT JOIN tbl_empresaoferta eo ON eo.idinforme = ia.idinf
--LEFT JOIN tbl_empreses em ON eo.cifempresa = em.cif
LEFT JOIN tbl_personaexpedient pe ON pe.idinf = ia.idinf AND pe.funcio = 'Responsable contracte'
JOIN tbl_centres c ON a.idcentre = c.codi
WHERE DATE(a.datacre) = :fecha
   OR DATE(a.datamodificacio) = :fecha
SQL;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['fecha' => $fecha]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse($result);
} catch (PDOException $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
