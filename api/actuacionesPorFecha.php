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
WITH base AS (
    SELECT DISTINCT ON (ia.idinf) ia.idinf AS codigo_informe,
      ia.idactuacio AS codigo_actuacion,
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
      em.cif || ' - ' || em.nom AS empresa,
      e.dataformalitzaciocontracte AS fecha_formalizacion,
      e.dataliquidacio AS fecha_liquidacion_contrato,
      e.dataficontracte AS fecha_cierre,
      a.datamodificacio AS fecha_modificacion
    FROM tbl_informeactuacio ia
    JOIN tbl_actuacio a ON ia.idactuacio = a.id
    JOIN tbl_propostesinforme pi ON pi.idinf = ia.idinf
    JOIN tbl_expedient e ON ia.expcontratacio = e.expcontratacio
    LEFT JOIN tbl_empresaoferta eo ON eo.idinforme = ia.idinf
    LEFT JOIN consulta_empreses em ON eo.cifempresa = em.cif
    LEFT JOIN tbl_personaexpedient pe ON pe.idinf = ia.idinf AND pe.funcio = 'Responsable contracte'
    JOIN tbl_centres c ON a.idcentre = c.codi
    WHERE DATE(a.datacre) = :fecha
       OR DATE(a.datamodificacio) = :fecha
    )
    SELECT
      b.*,
      COALESCE(tot.sum_expediente, 0)        AS importe_expediente,
      COALESCE(par.sum_partidas, 0)       AS importe_partidas,
      COALESCE(cer.sum_certificaciones, 0) AS importe_certificado,
      COALESCE(fac.sum_facturas, 0)        AS importe_facturado
    FROM base b

    -- TOTAL EXPEDIENTE
    LEFT JOIN LATERAL (
      SELECT SUM(t.plic) AS sum_expediente
      FROM tbl_propostesinforme t
      WHERE t.idinf = b.codigo_informe
    ) tot ON TRUE

    -- PARTIDAS (sumatorio de todas las partidas asignadas al expediente)
    LEFT JOIN LATERAL (
      SELECT SUM(p.valorpd) AS sum_partidas
      FROM consulta_partides p
      WHERE p.idinf = b.codigo_informe
    ) par ON TRUE

    -- CERTIFICACIONES (sumatorio de todas las certificaciones)
    LEFT JOIN LATERAL (
      SELECT SUM(cer.import) AS sum_certificaciones
      FROM consulta_certificacions cer
      WHERE cer.idinforme = b.codigo_informe
    ) cer ON TRUE

    -- FACTURAS (sumatorio de todas las facturas)
    LEFT JOIN LATERAL (
      SELECT SUM(f.import) AS sum_facturas
      FROM consulta_factures f
      WHERE f.idinforme = b.codigo_informe
    ) fac ON TRUE
SQL;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['fecha' => $fecha]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse($result);
} catch (PDOException $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
