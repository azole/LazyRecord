<?php
use SQLBuilder\Driver\PDOSQLiteDriver;
use LazyRecord\TableParser\SqliteTableParser;

class SqliteTableParserTest extends PHPUnit_Framework_TestCase
{
    public function testSQLiteTableParser()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        ok($pdo);

        $pdo->query('CREATE TABLE foo ( id integer primary key autoincrement, name varchar(12), phone varchar(32) unique , address text not null );');
        $pdo->query('CREATE TABLE bar ( id integer primary key autoincrement, confirmed boolean default false, content blob );');

        $parser = new SqliteTableParser(new PDOSQLiteDriver($pdo),$pdo);
        $tables = $parser->getTables();

        $this->assertNotEmpty($tables);
        $this->assertCount(2, $tables);

        $sql = $parser->getTableSql('foo');
        ok($sql);

        $columns = $parser->parseTableSql('foo');
        $this->assertNotEmpty($columns);

        $columns = $parser->parseTableSql('bar');
        $this->assertNotEmpty($columns);

        $schema = $parser->reverseTableSchema('bar');
        $this->assertNotNull($schema);

        $id = $schema->getColumn('id');
        $this->assertNotNull($id);
        $this->assertTrue($id->autoIncrement);
        $this->assertEquals('INTEGER',$id->type);
        $this->assertEquals('int',$id->isa);
        $this->assertTrue($id->primary);
    }
}

