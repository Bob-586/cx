<?php

function ssp_get_limit() {
    if ( isset($_GET['start']) && $_GET['length'] != -1 ) {
			return intval($_GET['start']).", ".intval($_GET['length']);
		} 
    return "";
}

function ssp_get_order($columns, $orderby_clause="", $skip = '') {
  $order = '';
  $add_it = false;
		if ( isset($_GET['order']) && count($_GET['order']) ) {
			$orderBy = array();
			$dtColumns = ssp_pluck( $columns, 'dt' );
			for ( $i=0, $ien=count($_GET['order']) ; $i<$ien ; $i++ ) {
				// Convert the column index into the column data property
				$columnIdx = intval($_GET['order'][$i]['column']);
				$requestColumn = $_GET['columns'][$columnIdx];
				$columnIdx = array_search( $requestColumn['data'], $dtColumns );
				$column = $columns[ $columnIdx ];
				if ( $requestColumn['orderable'] == 'true' ) {
					$dir = $_GET['order'][$i]['dir'] === 'asc' ? 'ASC' : 'DESC';
          $orderBy[] = \cx\app\main_functions::fix_db_column($column['db']) . ' ' . $dir;
          $add_it = true;
				}
			}
      
      if ($add_it === false) {
        return $skip;
      }
      
			$order = " {$orderby_clause}".implode(', ', $orderBy);
		}
    
  return $order;  
}

function ssp_get_where($columns, $request, &$bindings ) {
		$globalSearch = array();
		$columnSearch = array();
		$dtColumns = ssp_pluck( $columns, 'dt' );
		if ( isset($request['search']) && $request['search']['value'] != '' ) {
			$str = $request['search']['value'];
			for ( $i=0, $ien=count($request['columns']) ; $i<$ien ; $i++ ) {
				$requestColumn = $request['columns'][$i];
				$columnIdx = array_search( $requestColumn['data'], $dtColumns );
				$column = $columns[ $columnIdx ];
				if ( $requestColumn['searchable'] == 'true' ) {
          $check = false;

          if (isset($column['fn']) && function_exists($column['fn'])) {
            $check = true;
            $funct = $column['fn'];
          }
         
          if ($check === true) {
            $binding = ssp_bind( $bindings, '%' . $funct($str) . '%', PDO::PARAM_STR );
          } else {
            $binding = ssp_bind( $bindings, '%'.$str.'%', PDO::PARAM_STR );
          }
          $globalSearch[] = \cx\app\main_functions::fix_db_column($column['db']) . " LIKE " . $binding;
				}
			}
		}
    $clm = (isset($request['columns'])) ? $request['columns'] : false;
    if ($clm !== false) {
      // Individual column filtering
      for ( $i=0, $ien=count($clm) ; $i<$ien ; $i++ ) {
        $requestColumn = $request['columns'][$i];
        $columnIdx = array_search( $requestColumn['data'], $dtColumns );
        $column = $columns[ $columnIdx ];
        $str = $requestColumn['search']['value'];
        if ( $requestColumn['searchable'] == 'true' &&
          $str != '' ) {
          $check = false;

          if (isset($column['fn']) && function_exists($column['fn'])) {
            $check = true;
            $funct = $column['fn'];
          }

          if ($check === true) {
            $binding = ssp_bind( $bindings, '%' . $funct($str) . '%', PDO::PARAM_STR );
          } else {
            $binding = ssp_bind( $bindings, '%'.$str.'%', PDO::PARAM_STR );  
          }
          $columnSearch[] = \cx\app\main_functions::fix_db_column($column['db']) . " LIKE " . $binding;
        }
      }
    }
    
// Combine the filters into a single string
		$where = '';
		if ( count( $globalSearch ) ) {
			$where = '('.implode(' OR ', $globalSearch).')';
		}

		if ( count( $columnSearch ) ) {
			$where = ($where === '') ?
				implode(' AND ', $columnSearch) :
				$where .' AND '. implode(' AND ', $columnSearch);
		}

    if ( $where !== '' ) {
			$where = ' '.$where. ' AND ';
		}
		return $where;
}

function ssp_do_bindings(&$stmt, $bindings) {
  if ( is_array( $bindings ) ) {
			for ( $i=0, $ien=count($bindings) ; $i<$ien ; $i++ ) {
				$binding = $bindings[$i];
				$stmt->bindValue( $binding['key'], $binding['val'], $binding['type'] );
			}
	}
}

function ssp_pluck( $a, $prop ) {
		$out = array();
		for ( $i=0, $len=count($a) ; $i<$len ; $i++ ) {
			$out[] = $a[$i][$prop];
		}
		return $out;
}

function ssp_bind( &$a, $val, $type ) {
		$key = ':binding_'.count( $a );
		$a[] = array(
			'key' => $key,
			'val' => $val,
			'type' => $type
		);
		return $key;
}

function ssp_output($numrows, $obj_model, $columns) {
  $draw = (isset($_GET['draw'])) ? $_GET['draw'] : 1;
    
  echo '{
      "draw": '.intval($draw).',
      "recordsTotal": '.$numrows.',
      "recordsFiltered": '.$numrows.',			
      ';

  if($numrows > 0){

    echo '"data":[';

    $first = true;
    $Column = array();

    $allow_html = true;
    $rows = $obj_model->get_members($allow_html);

    foreach($rows as $row) {
      if($first) {
        $first = false;
      } else {
        echo ',';
      }

      foreach($columns as $column) {
        $db_col = \cx\app\main_functions::get_db_column($column['db']);
        
        if (isset($column['fn_results']) && function_exists($column['fn_results'])) {
          $funct = $column['fn_results'];
          $out = $funct($row[$db_col]);
        } else {
          $out = $row[$db_col];
        }

        if (isset($column['textsize']) && strlen($row[$db_col]) > $column['textsize']) {
          $out = substr(strip_tags($out), 0, $column['textsize']);
        }        
        
        if (isset($column['hyper'])) {
          $hyper = $column['hyper'];
          if (isset($column['id'])) {
            $db_id_col = \cx\app\main_functions::get_db_column($column['id']);
            $hlink = $hyper . $row[$db_id_col];
          } else {
            $hlink = $hyper;
          }
          $Column[] = "<a href='{$hlink}'>{$out}</a>";
        } else {
          $Column[] = $out;
        }
      }

      echo json_encode($Column);
      $Column = '';
      $Column = array();
    }
    echo ']}';
  } else {
    echo '"data":[]}';
  }
  
}