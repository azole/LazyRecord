<?php
namespace LazyRecord\Schema\SqlBuilder;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\QueryBuilder;

/**
 * Schema SQL builder
 *
 * @see http://www.sqlite.org/docs.html
 */
class SqliteBuilder
    extends BaseBuilder
    implements BuilderInterface
{

    function buildColumnSql($schema, $column) {      
        $name = $column->name;
        $isa  = $column->isa ?: 'str';
        $type = $column->type;
        if( ! $type && $isa == 'str' )
            $type = 'text';

        $sql = $this->parent->driver->getQuoteColumn( $name );
        $sql .= ' ' . $type;

        if( $column->required || $column->notNull )
            $sql .= ' NOT NULL';
        elseif( $column->null )
            $sql .= ' NULL';

        /**
         * if it's callable, we should not write the result into sql schema 
         */
        if( null !== ($default = $column->default) 
            && ! is_callable($column->default )  ) 
        {
            // for raw sql default value
            if( is_array($default) ) {
                $sql .= ' default ' . $default[0];
            } else {
                $sql .= ' default ' . $this->parent->driver->inflate($default);
            }
        }

        if( $column->primary )
            $sql .= ' primary key';

        if( $column->autoIncrement )
            $sql .= ' autoincrement';

        if( $column->unique )
            $sql .= ' unique';

        /**
         * build sqlite reference
         *    create table track(
         *        trackartist INTEGER,
         *        FOREIGN KEY(trackartist) REFERENCES artist(artistid)
         *    )
         * @see http://www.sqlite.org/foreignkeys.html
        */
        foreach( $schema->relations as $rel ) {
            switch( $rel['type'] ) {
            case SchemaDeclare::belongs_to:
            case SchemaDeclare::has_many:
            case SchemaDeclare::has_one:
                if( $name != 'id' && $rel['self']['column'] == $name ) 
                {
                    $fSchema = new $rel['foreign']['schema'];
                    $fColumn = $rel['foreign']['column'];
                    $fc = $fSchema->columns[$fColumn];
                    $sql .= ' REFERENCES ' . $fSchema->getTable() . '(' . $fColumn . ')';
                }
                break;
            }
        }
        return $sql;
    }

    public function build($schema)
    {
        $sqls = array();

        if( $this->parent->rebuild ) {
            $sqls[] = 'DROP TABLE IF EXISTS ' 
                . $this->parent->driver->getQuoteTableName( $schema->getTable() );
        }

        $sql = 'CREATE TABLE ' 
            . $this->parent->driver->getQuoteTableName($schema->getTable()) . " ( \n";
        $columnSql = array();
        foreach( $schema->columns as $name => $column ) {
            $columnSql[] = $this->buildColumnSql( $schema, $column );
        }
        $sql .= join(",\n",$columnSql);
        $sql .= "\n);\n";
        $sqls[] = $sql;
        return $sqls;
    }

}
