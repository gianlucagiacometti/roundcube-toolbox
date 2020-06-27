<?php

class rcube_toolbox_storage_sql_helper
{

    private $db;
    private $dsn;

    public function __construct($db, $dsn)
    {
        $this->db = $db;
        $this->dsn = $dsn;
    }

    // convert timestamp/datetime to formatted string
    public function to_char($field, $format)
    {
        $dsn = $this->db::parse_dsn($this->dsn);
        $format = $format[$dsn['phptype']];
        switch ($dsn['phptype']) {
            case 'pgsql':
                // https://www.postgresql.org/docs/10/static/functions-formatting.html#FUNCTIONS-FORMATTING-DATETIME-TABLE
                return "to_char({$field}, '{$format}') as {$field}";
                break;
            case 'mysql':
                // https://dev.mysql.com/doc/refman/8.0/en/date-and-time-functions.html#function_date-format
                return "DATE_FORMAT({$field}, '{$format}') as {$field}";
                break;
            case 'sqlite':
                // https://www.sqlite.org/lang_datefunc.html
                return "strftime('{$format}', {$field}) as {$field}";
                break;
            case 'oracle':
                // https://docs.oracle.com/cd/B19306_01/server.102/b14200/functions180.htm
                return "to_char({$field}, '{$format}') as {$field}";
                break;
            case 'mssql':
                // https://docs.microsoft.com/en-us/sql/t-sql/functions/cast-and-convert-transact-sql?view=sql-server-2017
                switch ($format) {
                    case 'mm/dd/yy':
                        return "CONVERT(varchar(8), {$field}, 1) as {$field}";
                        break;
                    case 'mm/dd/yyyy':
                        return "CONVERT(varchar(10), {$field}, 101) as {$field}";
                        break;
                    case 'yy.mm.dd':
                        return "CONVERT(varchar(8), {$field}, 2) as {$field}";
                        break;
                    case 'yyyy.mm.dd':
                        return "CONVERT(varchar(8), {$field}, 102) as {$field}";
                        break;
                    case 'dd/mm/yy':
                        return "CONVERT(varchar(8), {$field}, 3) as {$field}";
                        break;
                    case 'dd/mm/yyyy':
                        return "CONVERT(varchar(10), {$field}, 103) as {$field}";
                        break;
                    case 'dd.mm.yy':
                        return "CONVERT(varchar(8), {$field}, 4) as {$field}";
                        break;
                    case 'dd.mm.yyyy':
                        return "CONVERT(varchar(10), {$field}, 104) as {$field}";
                        break;
                    case 'dd-mm-yy':
                        return "CONVERT(varchar(8), {$field}, 5) as {$field}";
                        break;
                    case 'dd-mm-yyyy':
                        return "CONVERT(varchar(10), {$field}, 105) as {$field}";
                        break;
                    case 'dd mon yy':
                        return "CONVERT(varchar(9), {$field}, 6) as {$field}";
                        break;
                    case 'dd mon yyyy':
                        return "CONVERT(varchar(11), {$field}, 106) as {$field}";
                        break;
                    case 'Mon dd, yy':
                        return "CONVERT(varchar(10), {$field}, 7) as {$field}";
                        break;
                    case 'Mon dd, yyyy':
                        return "CONVERT(varchar(12), {$field}, 107) as {$field}";
                        break;
                    case 'hh:mi:ss':
                        return "CONVERT(varchar(8), {$field}, 8) as {$field}";
                        break;
                    case 'mon dd yyyy hh:mi:ss:mmmAM':
                        return "CONVERT(varchar(26), {$field}, 9) as {$field}";
                        break;
                    case 'mm-dd-yy':
                        return "CONVERT(varchar(8), {$field}, 10) as {$field}";
                        break;
                    case 'mm-dd-yyyy':
                        return "CONVERT(varchar(10), {$field}, 110) as {$field}";
                        break;
                    case 'yy/mm/dd':
                        return "CONVERT(varchar(8), {$field}, 11) as {$field}";
                        break;
                    case 'yyyy/mm/dd':
                        return "CONVERT(varchar(10), {$field}, 111) as {$field}";
                        break;
                    case 'yymmdd':
                        return "CONVERT(varchar(6), {$field}, 12) as {$field}";
                        break;
                    case 'yyyymmdd':
                        return "CONVERT(varchar(8), {$field}, 112) as {$field}";
                        break;
                    case 'dd mon yyyy hh:mi:ss:mmm':
                        return "CONVERT(varchar(24), {$field}, 13) as {$field}";
                        break;
                    case 'hh:mi:ss:mmm':
                        return "CONVERT(varchar(12), {$field}, 14) as {$field}";
                        break;
                    case 'yyyy-mm-dd hh:mi:ss':
                        return "CONVERT(varchar(19), {$field}, 20) as {$field}";
                        break;
                    case 'yyyy-mm-dd hh:mi:ss.mmm':
                        return "CONVERT(varchar(23), {$field}, 21) as {$field}";
                        break;
                    case 'yyyy-mm-ddThh:mi:ss.mmm':
                        return "CONVERT(varchar(23), {$field}, 126) as {$field}";
                        break;
                    case 'yyyy-mm-ddThh:mi:ss.mmmZ':
                        return "CONVERT(varchar(24), {$field}, 127) as {$field}";
                        break;
                    default:
                        return "CONVERT(varchar(19), {$field}, 0) as {$field}";
                        break;
                }
                break;
        }
    }

    // convert formatted string into timestamp/datetime
    public function to_timestamp($field, $format)
    {
        $date = DateTime::createFromFormat($format['php'], $date);
        $dsn = $this->db::parse_dsn($this->dsn);
        $format = $format[$dsn['phptype']];
        switch ($dsn['phptype']) {
            case 'pgsql':
                // https://www.postgresql.org/docs/10/static/functions-formatting.html
                return "to_timestamp('{$field}', '{$format}')";
                break;
            case 'mysql':
                // https://dev.mysql.com/doc/refman/8.0/en/date-and-time-functions.html
                return "'{$date->format('Y-m-d H:i:s')}'";
                break;
            case 'sqlite':
                // https://www.sqlite.org/lang_datefunc.html
                return "'{$date->format('Y-m-d H:i:s')}'";
                break;
            case 'oracle':
                // https://docs.oracle.com/cd/B19306_01/server.102/b14200/functions180.htm
                return "to_timestamp('{$field}', '{$format}')";
                break;
            case 'mssql':
                // https://docs.microsoft.com/en-us/sql/t-sql/functions/cast-and-convert-transact-sql?view=sql-server-2017
                return "'{$date->format('Y-m-dTH:i:s')}'";
                break;
        }
    }

}

// END OF FILE
