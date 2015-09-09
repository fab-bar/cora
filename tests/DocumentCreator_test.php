<?php
require_once 'DB_fixture.php';
require_once 'DocumentAccessor_mocks.php';
require_once 'data/test_data.php';
require_once "{$GLOBALS['CORA_WEB_DIR']}/lib/connect.php";
require_once "{$GLOBALS['CORA_WEB_DIR']}/lib/connect/DocumentCreator.php";

/** Tests for DocumentCreator class
 */
class Cora_Tests_DocumentCreator_test extends Cora_Tests_DbTestCase {
  private $dbi;
  private $dbo;
  private $obj; /**< The object under test */

  public function setUp() {
    //$this->dbi = new Cora_Tests_DBInterface_Mock();
    $dbinfo = array(
      'HOST' => $GLOBALS["DB_HOST"],
      'USER' => $GLOBALS["DB_USER"],
      'PASSWORD' => $GLOBALS["DB_PASSWD"],
      'DBNAME' => $GLOBALS["DB_DBNAME"]
    );
    $this->dbi = new DBInterface($dbinfo);
    $this->dbo = new PDO($GLOBALS["DB_DSN"],
                         $GLOBALS["DB_USER"],
                         $GLOBALS["DB_PASSWD"]);
    parent::setUp();
  }

  public function testInsertNewDocument() {
    $options = array("tagset" => "1",
                     "sigle" => "i1",
                     "name" => "importtest",
                     "project" => 1,
		     "tagsets" => array("1","2","3")
    );
    $data = new Cora_Tests_CoraDocument_Mock();
    $expected = $this->createXMLDataset("data/inserted_document.xml");

    $this->obj = new DocumentCreator($this->dbi, $this->dbo, $options);
    $this->assertTrue($this->obj->importDocument($data, 3));
    $this->assertEmpty($this->obj->getWarnings());

    $this->assertTablesEqual($expected->getTable("inserted_text"),
      $this->getConnection()->createQueryTable("inserted_text",
        "SELECT id, sigle, fullname, project_id, currentmod_id, header FROM text "
        ."WHERE sigle='i1'"));
    $this->assertTablesEqual($expected->getTable("inserted_text2tagset"),
       $this->getConnection()->createQueryTable("inserted_text2tagset",
       "SELECT * FROM text2tagset WHERE text_id=6"));

    $this->assertTablesEqual($expected->getTable("inserted_page"),
      $this->getConnection()->createQueryTable("inserted_page",
        "SELECT * FROM page WHERE text_id=6"));

    $this->assertTablesEqual($expected->getTable("inserted_col"),
      $this->getConnection()->createQueryTable("inserted_col",
        "SELECT * FROM col WHERE page_id=3"));

    $this->assertTablesEqual($expected->getTable("inserted_line"),
      $this->getConnection()->createQueryTable("inserted_line",
        "SELECT * FROM line WHERE col_id=3"));

    $this->assertTablesEqual($expected->getTable("inserted_token"),
      $this->getConnection()->createQueryTable("inserted_token",
        "SELECT * FROM token WHERE text_id=6"));

    $this->assertTablesEqual($expected->getTable("inserted_dipl"),
      $this->getConnection()->createQueryTable("inserted_dipl",
        "SELECT * FROM dipl WHERE tok_id >= 7 AND tok_id <= 9"));

    $this->assertTablesEqual($expected->getTable("inserted_modern"),
      $this->getConnection()->createQueryTable("inserted_modern",
        "SELECT * FROM modern WHERE tok_id >= 7 AND tok_id <= 9"));

    $this->assertTablesEqual($expected->getTable("inserted_tag_suggestion"),
      $this->getConnection()->createQueryTable("inserted_tag_suggestion",
        "SELECT * FROM tag_suggestion WHERE mod_id >= 15"));

    $this->assertTablesEqual($expected->getTable("inserted_tag"),
      $this->getConnection()->createQueryTable("inserted_tag",
        "SELECT * FROM tag WHERE tagset_id=2 or tagset_id=3"));

    $this->assertTablesEqual($expected->getTable("inserted_comment"),
      $this->getConnection()->createQueryTable("inserted_comment",
        "SELECT * FROM comment WHERE tok_id=7"));

    $this->assertTablesEqual($expected->getTable("inserted_mod2error"),
      $this->getConnection()->createQueryTable("inserted_mod2error",
        "SELECT * FROM mod2error WHERE mod_id >= 15"));
    }
}

?>
