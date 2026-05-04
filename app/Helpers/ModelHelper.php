<?php

namespace App\Helpers;

use App\Jobs\RecalculateCOGS;
use App\Models\Companies\v1\Permissions;
use App\Models\Companies\v1\RoleHasPermissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Str;

class ModelHelper
{
	private static $operators = [
		"\$gt" => ">",
		"\$gte" => ">=",
		"\$lte" => "<=",
		"\$lt" => "<",
		"\$like" => "like",
		"\$ilike" => "ilike",
		"\$not" => "<>",
		"\$in" => "in"
	];

	public static function select($schema, $request = null, $class)
	{
		$selects = [];
		$params = [];

		if ($request) {
			$params = $request->all();
		}

		if (isset($params['select']) && $params['select']) {
			$selects = explode(',', $params['select']);
		}

		$_select = [];

		foreach (array_values($schema) as $select) {
			if ($selects) {
				if (in_array($select['alias'], $selects)) {
					if (isset($select['is_raw']) && $select['is_raw']) {
						$_select[] = DB::raw($select['column'] . ' as ' . $select['alias']);
					} else {
						$_select[] = $select['column'] . ' as ' . $select['alias'];
					}
				}
			} else {
				if (isset($select['is_raw']) && $select['is_raw']) {
					$_select[] = DB::raw($select['column'] . ' as ' . $select['alias']);
				} else {
					$_select[] = $select['column'] . ' as ' . $select['alias'];
				}
			}
		}

		return $class::select($_select);
	}

	public static function join($schema, $request = null, $model)
	{
		foreach ($schema as $join) {
			if ($join['type'] == 'left') {
				if (!is_array($join['on'][0])) {
					if (count($join['on']) < 3) {
						$model->leftJoin($join['table'], function ($q) use ($join) {
							foreach ($join['on'] as $single_join) {
								if (count($single_join) > 3) {
									if (!$single_join[3]) {
										$q->on($single_join[0], $single_join[1], $single_join[2]);
									} else {
										$q->on($single_join[0], $single_join[1], DB::raw("'" . $single_join[2] . "'"));
									}
								} else {
									$q->on($single_join[0], $single_join[1], DB::raw("'" . $single_join[2] . "'"));
								}
							}
						});
					} else {
						$model->leftJoin($join['table'], function ($q) use ($join) {
							$q->on([$join['on']]);
							if (isset($join['is_softdelete']) && $join['is_softdelete']) {
								$q->whereNull($join['table'] . '.deleted_at');
							}
						});
					}
				} else {
					$model->leftJoin($join['table'], function ($q) use ($join) {
						// dd($join['on']);
						foreach ($join['on'] as $key => $join_on) {
							if (count($join_on) < 3) {
								foreach ($join_on as $single_join) {
									if (count($single_join) > 3) {
										if (!$single_join[3]) {
											$q->on($single_join[0], $single_join[1], $single_join[2]);
										} else {
											$q->on($single_join[0], $single_join[1], DB::raw("'" . $single_join[2] . "'"));
										}
									} else {
										$q->on($single_join[0], $single_join[1], DB::raw("'" . $single_join[2] . "'"));
									}
								}
							} else {
								$join_type = false;
								if (isset($join_on[3]) && $join_on[3]) {
									$join_type = $join_on[3];
								}

								if ($join_type == 'andWhere') {
									if ($join_on[1] == '=' && $join_on[2] == 'null') {
										$q->whereNull($join_on[0]);
									} else {
										$q->where($join_on[0], $join_on[1], $join_on[2]);
									}
								} else if ($join_type == 'orWhere') {
									if ($join_on[1] == '=' && $join_on[2] == 'null') {
										$q->orWhereNull($join_on[0]);
									} else {
										$q->orWhere($join_on[0], $join_on[1], $join_on[2]);
									}
								} else if (!$join_type) {
									// if ($key > 0) {
									// 	dd($join_on[0], $join_on[1], $join_on[2]);
									// }
									$q->on($join_on[0], $join_on[1], $join_on[2]);
								} else {
									$q->on($join_on[0], $join_on[1], DB::raw("'" . $join_on[2] . "'"));
								}
								if (isset($join['is_softdelete']) && $join['is_softdelete']) {
									$q->whereNull($join['table'] . '.deleted_at');
								}
							}
						}
					});
				}
			} else {
				$model->join($join['table'], [$join['on']]);
			}
		}
	}

	public static function dynamicFilterAnd($params, $request, $model, $class)
	{
		foreach (array($params) as $k => $v) {
			foreach (array_keys($v) as $key => $row) {
				if (isset($class::mapSchema()['field'][$row])) {
					$column = $class::mapSchema()['field'][$row]['column'];
					if (isset($class::mapSchema()['field'][$row]['is_raw']) && $class::mapSchema()['field'][$row]['is_raw']) {
						$column = DB::raw($column);
					}

					if (is_array(array_values($v)[$key])) {
						if (count(array_values($v)[$key]) > 0) {
							foreach (array_values($v)[$key] as $keyOpr => $valOpr) {
								if (self::$operators[$keyOpr] != 'ilike') {
									if (self::$operators[$keyOpr] == '<>' && $valOpr == 'null') {
										$model->whereNotNull($column);
										if ($class::mapSchema()['field'][$row]['type'] == 'int') {
											$model->where($column, '!=', '0');
										} else {
											$model->where($column, '!=', '');
										}
									} else {
										$model->where($column, self::$operators[$keyOpr], pg_escape_string($valOpr));
									}
								} else {
									$model->where($column, 'ilike', '%' . pg_escape_string($valOpr) . '%');
								}
							}
						}
					} else {
						if ($class::mapSchema()['field'][$row]['type'] === 'int') {
							if (array_values($v)[$key] != 'null' && array_values($v)[$key] != null) {
								$model->where($column, pg_escape_string(array_values($v)[$key]));
							} else if (array_values($v)[$key] == 'null' || array_values($v)[$key] == null) {
								$model->whereNull($column);
							} else if (array_values($v)[$key] == 'not_null') {
								$model->whereNotNull($column);
							}
						} else if (isset($class::mapSchema()['field'][$row]['like'])) {
							if ($class::mapSchema()['field'][$row]['like'] == '%_') {
								$model->where($column, 'ilike', '%' . pg_escape_string(array_values($v)[$key]));
							} else if ($class::mapSchema()['field'][$row]['like'] == '_%') {
								$model->where($column, 'ilike', pg_escape_string(array_values($v)[$key]) . '%');
							} else if ($class::mapSchema()['field'][$row]['like'] == '%_%') {
								$model->where($column, 'ilike', '%' . pg_escape_string(array_values($v)[$key]) . '%');
							} else {
								$model->where($column, 'ilike', '%' . pg_escape_string(array_values($v)[$key]) . '%');
							}
						} else {
							if (array_values($v)[$key] != 'null') {
								$model->where($column, 'ilike', '%' . pg_escape_string(array_values($v)[$key]) . '%');
							} else if (array_values($v)[$key] == 'null') {
								$model->whereNull($column);
							} else if (array_values($v)[$key] == 'not_null') {
								$model->whereNotNull($column);
							}
						}
					}
				}
			}
		}
	}

	public static function dynamicFilterOr($params, $request, $model, $class)
	{
		$n = 0;
		$comparison_total = -1;

		foreach ($params as $orKey => $orVal) {
			if (isset($class::mapSchema()['field'][$orKey])) {
				$explode_if_got_separator = explode('||', $orVal);
				foreach ($explode_if_got_separator as $val) {
					$comparison_total += 1;
				}
			}
		}

		// foreach($params as $orKey => $orVal) {
		//     if (isset($class::mapSchema()['field'][$orKey])) {
		//     	if (is_array($orVal)) {
		//     		$vars = array_values($orVal);
		//     		$vars = implode('||', $vars);
		//     	} else {
		//     		$vars = $orVal;
		//     	}

		//     	$explode_if_got_separator = explode('||', $vars);
		//     	foreach ($explode_if_got_separator as $val) {
		//     		$comparison_total += 1;
		//     	}
		//     }
		// }

		foreach ($params as $orKey => $orVal) {
			if (isset($class::mapSchema()['field'][$orKey])) {
				$explode_if_got_separator = explode('||', $orVal);
				foreach ($explode_if_got_separator as $val) {
					if ($val == 'null') {
						if ($n < 1) {
							$model->whereRaw('( ' . $class::mapSchema()['field'][$orKey]['column'] . ' IS NULL');
						} else if ($n > 0 && $n < $comparison_total) {
							$model->orWhereRaw($class::mapSchema()['field'][$orKey]['column'] . ' IS NULL');
						} else {
							$model->orWhereRaw($class::mapSchema()['field'][$orKey]['column'] . ' IS NULL)');
						}
					} else if ($val == 'not_null') {
						if ($n < 1) {
							$model->whereRaw('( ' . $class::mapSchema()['field'][$orKey]['column'] . ' IS NOT NULL');
						} else if ($n > 0 && $n < $comparison_total) {
							$model->orWhereRaw($class::mapSchema()['field'][$orKey]['column'] . ' IS NOT NULL');
						} else {
							$model->orWhereRaw($class::mapSchema()['field'][$orKey]['column'] . ' IS NOT NULL)');
						}
					} else {
						if (strpos($val, '[$not]') !== false) {
							$val = str_replace('[$not]', '', $val);

							if ($n < 1) {
								$model->whereRaw('( ' . $class::mapSchema()['field'][$orKey]['column'] . ' != \'' . pg_escape_string($val) . '\'');
							} else if ($n > 0 && $n < $comparison_total) {
								$model->orWhereRaw($class::mapSchema()['field'][$orKey]['column'] . ' != \'' . pg_escape_string($val) . '\'');
							} else {
								$model->orWhereRaw($class::mapSchema()['field'][$orKey]['column'] . ' != \'' . pg_escape_string($val) . '\' )');
							}
						} else if ($class::mapSchema()['field'][$orKey]['type'] === 'int') {
							if ($n < 1) {
								$model->whereRaw('( ' . $class::mapSchema()['field'][$orKey]['column'] . ' = \'' . pg_escape_string($val) . '\'');
							} else if ($n > 0 && $n < $comparison_total) {
								$model->orWhereRaw($class::mapSchema()['field'][$orKey]['column'] . ' = \'' . pg_escape_string($val) . '\'');
							} else {
								$model->orWhereRaw($class::mapSchema()['field'][$orKey]['column'] . ' = \'' . pg_escape_string($val) . '\' )');
							}
						} else if (isset($class::mapSchema()['field'][$orKey]['like'])) {
							if ($class::mapSchema()['field'][$orKey]['like'] == '%_') {
								if ($n < 1) {
									$model->whereRaw('( ' . $class::mapSchema()['field'][$orKey]['column'] . ' ilike \'%' . pg_escape_string($val) . '\'');
								} else if ($n > 0 && $n < $comparison_total) {
									$model->orWhereRaw($class::mapSchema()['field'][$orKey]['column'] . ' ilike \'%' . pg_escape_string($val) . '\'');
								} else {
									$model->orWhereRaw($class::mapSchema()['field'][$orKey]['column'] . ' ilike \'%' . pg_escape_string($val) . '\')');
								}
							} else if ($class::mapSchema()['field'][$orKey]['like'] == '_%') {
								if ($n < 1) {
									$model->whereRaw('( ' . $class::mapSchema()['field'][$orKey]['column'] . ' ilike \'' . pg_escape_string($val) . '%\'');
								} else if ($n > 0 && $n < $comparison_total) {
									$model->orWhereRaw($class::mapSchema()['field'][$orKey]['column'] . ' ilike \'' . pg_escape_string($val) . '%\'');
								} else {
									$model->orWhereRaw($class::mapSchema()['field'][$orKey]['column'] . ' ilike \'' . pg_escape_string($val) . '%\')');
								}
							} else if ($class::mapSchema()['field'][$orKey]['like'] == '%_%') {
								$model->where($class::mapSchema()['field'][$orKey]['column'], 'ilike', '%' . pg_escape_string($val) . '%');
								if ($n < 1) {
									$model->whereRaw('( ' . $class::mapSchema()['field'][$orKey]['column'] . ' ilike \'%' . pg_escape_string($val) . '%\'');
								} else if ($n > 0 && $n < $comparison_total) {
									$model->orWhereRaw($class::mapSchema()['field'][$orKey]['column'] . ' ilike \'%' . pg_escape_string($val) . '%\'');
								} else {
									$model->orWhereRaw($class::mapSchema()['field'][$orKey]['column'] . ' ilike \'%' . pg_escape_string($val) . '%\')');
								}
							} else {
								$model->where($class::mapSchema()['field'][$orKey]['column'], 'ilike', '%' . pg_escape_string($val) . '%');
								if ($n < 1) {
									$model->whereRaw('( ' . $class::mapSchema()['field'][$orKey]['column'] . ' ilike \'%' . pg_escape_string($val) . '%\'');
								} else if ($n > 0 && $n < $comparison_total) {
									$model->orWhereRaw($class::mapSchema()['field'][$orKey]['column'] . ' ilike \'%' . pg_escape_string($val) . '%\'');
								} else {
									$model->orWhereRaw($class::mapSchema()['field'][$orKey]['column'] . ' ilike \'%' . pg_escape_string($val) . '%\')');
								}
							}
						} else {
							if ($n < 1) {
								$model->whereRaw('( ' . $class::mapSchema()['field'][$orKey]['column'] . ' ilike \'%' . pg_escape_string($val) . '%\'');
							} else if ($n > 0 && $n < $comparison_total) {
								$model->orWhereRaw($class::mapSchema()['field'][$orKey]['column'] . ' ilike \'%' . pg_escape_string($val) . '%\'');
							} else {
								$model->orWhereRaw($class::mapSchema()['field'][$orKey]['column'] . ' ilike \'%' . pg_escape_string($val) . '%\')');
							}
						}
					}
					$n++;
				}
			}
		}
	}

	public static function generateAllResults($schema, $params, $request, $model, $append = [])
	{
		if (isset($params['order']) && is_array($params['order'])) {
			$allowedFields = isset($schema['field'])
				? array_keys($schema['field'])
				: [];

			foreach ($params['order'] as $orderKey => $orderVal) {

				if (!in_array($orderKey, $allowedFields)) {
					continue;
				}

				if (!isset($schema['field'][$orderKey]['column'])) {
					continue;
				}

				$direction = strtolower($orderVal) === 'desc' ? 'desc' : 'asc';

				$model->orderBy(
					$schema['field'][$orderKey]['column'],
					$direction
				);
			}
		}
		// dd(GLobalHelper::getRealQuery($model, true));
		$data = $model->get();

		foreach ($data as $idx => $row) {
			foreach ($row->toArray() as $key => $val) {
				if (array_key_exists($key, $schema['field']) && isset($schema['field'][$key]['type']) && $schema['field'][$key]['type'] == 'array' && !empty($data[$idx][$key])) {
					$data[$idx][$key] = json_decode($data[$idx][$key], TRUE);
				}
			}
		}

		return self::response($data, false);
	}

	public static function generatePagingResults($schema, $page, $params, $request, $model, $append = [])
	{
		$per_page = 10;

		if (isset($params['order']) && is_array($params['order'])) {
			$allowedFields = isset($schema['field'])
				? array_keys($schema['field'])
				: [];

			foreach ($params['order'] as $orderKey => $orderVal) {

				if (!in_array($orderKey, $allowedFields)) {
					continue;
				}

				if (!isset($schema['field'][$orderKey]['column'])) {
					continue;
				}

				$direction = strtolower($orderVal) === 'desc' ? 'desc' : 'asc';

				$model->orderBy(
					$schema['field'][$orderKey]['column'],
					$direction
				);
			}
		}

		if (isset($params['per_page']) && $params['per_page'] > 0) {
			$per_page = $params['per_page'];
		}

		$countAll = $model->count();
		$currentPage = $page > 0 ? $page - 1 : 0;
		$page = $page > 0 ? $page + 1 : 2;
		$nextPage = $request->url() . '?page=' . $page;
		$prevPage = $request->url() . '?page=' . ($currentPage < 1 ? 1 : $currentPage);
		$totalPage = ceil((int)$countAll / $per_page);

		$model->skip($currentPage * $per_page)
			->take($per_page);

		$data = $model->get();

		foreach ($data as $idx => $row) {
			foreach ($row->toArray() as $key => $val) {
				if (isset($schema['field'][$key]['type']) && $schema['field'][$key]['type'] == 'array' && !empty($data[$idx][$key])) {
					$data[$idx][$key] = json_decode($data[$idx][$key], TRUE);
				}
			}
		}

		$results['totalData'] = $countAll;
		$results['nextPage'] = $nextPage;
		$results['prevPage'] = $prevPage;
		$results['totalPage'] = $totalPage;
		$results['data'] = $data;

		return self::response($results, true);
	}

	public static function response($params, $is_paging)
	{
		$results = $params;

		if ($is_paging) {
			$results = [
				'nav' => [
					'totalData' => $params['totalData'],
					'nextPage' => $params['nextPage'],
					'prevPage' => $params['prevPage'],
					'totalPage' => $params['totalPage']
				],
				'data' => $params['data']
			];
		}

		return $results;
	}

	public static function adjustSequencePostgreSql()
	{
		$expression = DB::raw("SELECT table_name FROM information_schema.tables WHERE table_schema='public' AND table_type='BASE TABLE'");
		$string = $expression->getValue(DB::connection('pgsql_companies')->getQueryGrammar());
		$tables = DB::connection('pgsql_companies')->select($string);
		
        foreach ($tables as $table) {

			$primary_key = DB::connection('pgsql_companies')
			->select(DB::raw(
			"SELECT pg_attribute.attname, format_type(pg_attribute.atttypid, pg_attribute.atttypmod) 
		    FROM pg_index, pg_class, pg_attribute, pg_namespace 
		    WHERE 
			pg_class.oid = '".$table->table_name."'::regclass AND 
			indrelid = pg_class.oid AND 
			nspname = 'public' AND 
			pg_class.relnamespace = pg_namespace.oid AND 
			pg_attribute.attrelid = pg_class.oid AND 
			pg_attribute.attnum = any(pg_index.indkey)
		   AND indisprimary")
		   ->getValue(DB::connection('pgsql_companies')->getQueryGrammar()));

            if ($table->table_name && isset($primary_key[0]->attname)) {
                $sequence_name = DB::connection('pgsql_companies')->select(DB::raw("SELECT * FROM information_schema.sequences WHERE sequence_name = '".$table->table_name."_".$primary_key[0]->attname."_seq' ")->getValue(DB::connection('pgsql_companies')->getQueryGrammar()));
                if (isset($sequence_name[0]->sequence_name)) {
                    DB::connection('pgsql_companies')->select(DB::raw("SELECT SETVAL('".$sequence_name[0]->sequence_name."', (SELECT MAX(".$primary_key[0]->attname.") + 1 FROM ".$table->table_name."))")->getValue(DB::connection('pgsql_companies')->getQueryGrammar()));
                }
            }
        }
	}

	public static function reorderPermissionAdmin()
	{
		RoleHasPermissions::where('role_id', 1)->delete();

		$filtered_data = [];

		$data = Permissions::get();

		foreach ($data as $row) {
			$filtered_data[] = [
				'permission_id' => $row['id'],
				'role_id' => 1
			];
		}

		RoleHasPermissions::insert($filtered_data);
	}

	public static function debugSql($query)
	{
		dd(Str::replaceArray('?', $query->getBindings(), $query->toSql()));
	}

	public static function migrationCompany()
	{
		$res = [];

		Artisan::call('migrate', ['--path' => 'database/migration_company', '--database' => 'pgsql_companies']);
		$res[] = Artisan::output();
		Artisan::call('migrate', ['--path' => 'database/migration_company_alter', '--database' => 'pgsql_companies']);
		$res[] = Artisan::output();

		return implode("\n", $res);
	}

	public static function recalculateInvByClusterID($selected_date, $selected_cluster_id, $old_date = null, $old_cluster_id = 0, $inventory_ids = null)
	{
		$recalculate_start_date = $selected_date;

		if ($old_date && $old_date < $selected_date) {
			$recalculate_start_date = $old_date;
		}

		$expl_selected_date = explode('-', $recalculate_start_date);
		$selected_year = $expl_selected_date[0];
		$selected_month = $expl_selected_date[1];
		$selected_date = $expl_selected_date[2];

		$recalculate_params['month'] = $selected_month;
		$recalculate_params['year'] = $selected_year;
		$recalculate_params['slug'] = config('database.connections.pgsql_companies.database');
		$recalculate_params['start_at'] = date('Y-m-d H:i:s');
		$recalculate_params['db_config'] = config('database.connections.pgsql_companies');
		$recalculate_params['general_settings'] = config('general_settings');
		if ($inventory_ids) {
			$recalculate_params['inventory_ids'] = $inventory_ids;
		}
		$recalculate_params['default_accounts'] = config('default_accounts');
		unset($recalculate_params['general_settings']['spr_terms']);
		unset($recalculate_params['general_settings']['work_agreement_terms']);

		if ($old_cluster_id > 0) {
			$recalculate_cluster_ids = [];
			if ($selected_cluster_id != $old_cluster_id) {
				$recalculate_cluster_ids = [
					$selected_cluster_id,
					$old_cluster_id
				];
			} else {
				$recalculate_cluster_ids = [
					$selected_cluster_id
				];
			}

			foreach ($recalculate_cluster_ids as $recalculate_cluster_id) {
				if ($recalculate_cluster_id > 0) {
					$recalculate_params['cluster_id'] = $recalculate_cluster_id;
					// AKTIFKAN JIKA DI SERVER
					RecalculateCOGS::dispatch($recalculate_params);

					// AKTIFKAN JIKA DI LOCAL
					// RecalculateCOGS::dispatchSync($recalculate_params);
				}
			}
		} else {
			if ($selected_cluster_id > 0) {
				$recalculate_params['cluster_id'] = $selected_cluster_id;
				// AKTIFKAN JIKA DI SERVER
				RecalculateCOGS::dispatch($recalculate_params);

				// AKTIFKAN JIKA DI LOCAL
				// RecalculateCOGS::dispatchSync($recalculate_params);
			}
		}
	}
}
