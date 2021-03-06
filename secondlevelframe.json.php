<?php
/**
 *
 * @package    mahara
 * @subpackage artefact-booklet
  * @author    Christophe DECLERCQ - christophe.declercq@univ-nantes.fr*
 * @author     Jean.Fruitet@univ-nantes.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 *
 */

define('INTERNAL', 1);
define('JSON', 1);
require(dirname(dirname(dirname(__FILE__))) . '/init.php');
safe_require('artefact', 'booklet');
global $USER;


$limit = param_integer('limit', null);
$offset = param_integer('offset', 0);
$type = param_alpha('type');
$id = param_integer('id', null);
$idframe = param_integer('idframe', null);

$data = array();
$count = 0;
$othertable = 'artefact_booklet_' . $type;

if ($type == 'visualization') {
    $objects = get_records_array('artefact_booklet_object', 'idframe', $id);
    $item = null;
    if ($objects) {
        foreach ($objects as $object) {
            if ((substr($object->name, 0, 5) == 'Title' || substr($object->name, 0, 5) == 'title') &&
                ($object->type == 'area' || $object->type == 'shorttext' || $object->type == 'longtext' || $object->type == 'htmltext' || $object->type == 'synthesis')) {
                $item = $object;
                break;
            }
        }
    }
    if (is_null($item)) {
        $sql = "SELECT *
                FROM {artefact_booklet_object}
                WHERE idframe = ?
                AND displayorder = (SELECT MIN(displayorder)
                                    FROM {artefact_booklet_object}
                                    WHERE idframe = ?
                                    AND (type='area'
                                         OR type='shorttext'
                                         OR type='longtext'
                                         OR type='synthesis'
                                         OR type='htmltext'))";
        $item = get_record_sql($sql, array($id, $id));
    }
    // Modif JF 2015/01/12
	// do est un mot protege sur Postgres
	$sql = "SELECT DISTINCT t.value, t.id FROM {artefact_booklet_resulttext} t
             JOIN {artefact_booklet_resultdisplayorder} d ON t.idrecord = d.idrecord AND t.idowner = d.idowner
             WHERE t.idobject = ?
             AND t.idowner = ?
             ORDER BY d.displayorder";

	if (!$data = get_records_sql_array($sql, array((($item)?$item->id:0), $USER->get('id')))) {
        $data = array();
    }
    $count = count($data);
}
// type <> visualization
else if ($type == 'frame') {   // ONLY SECONDLEVEL FRAME are displayed
    $sql = 'SELECT ar.* FROM {' . $othertable . '} ar WHERE ar.idtab = ? AND ar.idparentframe = ? ORDER BY ar.displayorder';
    if (!$data = get_records_sql_array($sql, array($id, $idframe))) {
        $data = array();
    }
    else {
        foreach ($data as $item) {
            if ($item->list == 1) {
                $item->list = get_string('yes', 'artefact.booklet');
            }
            else {
                $item->list = get_string('no', 'artefact.booklet');
            }
        }
    }
    $count = count_records($othertable, 'idtab', $id, 'idparentframe', $idframe );  // idtome
}
else{
	exit;
}

json_reply(false, array(
    'data' => $data,
    'limit' => $limit,
    'offset' => $offset,
    'count' => $count,
    'type' => $type,
    'id' => $id,
));
