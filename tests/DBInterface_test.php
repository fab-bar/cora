<?php
require_once "DB_fixture.php";
require_once "test_data.php";

require_once "../lib/connect.php";

/** Tests for DBInterface class
 *
 *  02/2013 Florian Petran
 *
 * DBInterface abstracts all operations that relate to the database.
 *
 * TODO
 * tests for:
 *      getUserData($user, $pw)
 *      getTagsets($class ="POS")
 *      getTagset($tagset, $limit="none")
 *      getTagsetByValue($tagset)
 *      changePassword($uname, $password)
 *      changeProjectUsers($pid, $userlist)
 *      toggleNormStatus($username)
 *      isAllowedToOpenFile($fid, $uname)
 *      isAllowedToDeleteFile($fid, $uname)
 *      getAllSuggestions($fid, $lineid)
 *      importTaglist($taglist, $tagsetname)
 *      insertNewDocument($options, $data) XXX
 *  coverage for:
 *      getLines($fid, $start, $lim);
 *      saveLines($fid, $lastedited, $lines);
 *
 */
class Cora_Tests_DBInterface_test extends Cora_Tests_DbTestCase {
    protected $dbi;
    protected $backupGlobalsBlacklist = array('_SESSION');
    protected $expected;

    protected function setUp() {
        $this->dbi = new DBInterface($this);
        $this->expected = test_data();
        parent::setUp();
    }

    public function testGetUser() {
        $this->assertEquals($this->expected["users"]["system"],
                            $this->dbi->getUserById(1));
        $this->assertEquals($this->expected["users"]["test"],
                            $this->dbi->getUserById(5));

        $this->assertEquals($this->expected["users"]["system"],
                            $this->dbi->getUserByName('system'));
        $this->assertEquals($this->expected["users"]["test"],
                            $this->dbi->getUserByName('test'));


        $this->assertEquals(1, $this->dbi->getUserIDFromName('system'));
        $this->assertEquals(5, $this->dbi->getUserIDFromName('test'));

        // TODO can't test this without the unhashed password
        // $this->dbi->getUserData($user,$pw);

        $this->assertEquals(array($this->expected["users"]["bollmann"],
                                  $this->expected["users"]["test"]),
                            $this->dbi->getUserList());
    }

    public function testUserActions() {
        // create user
        // creating a user that already exists should fail
        $this->assertFalse($this->dbi->createUser("test", "blabla", "0"));

        $this->dbi->createUser("anselm", "blabla", "0");
        $expected = $this->createXMLDataSet("created_user.xml");

        // TODO password hash breaks table equality, idk why
        $this->assertTablesEqual($expected->getTable("users"),
                                 $this->getConnection()->createQueryTable("users",
                                    "SELECT id,name,admin FROM users WHERE name='anselm';"));

        //changePassword($name, $passwd);

        $this->dbi->deleteUser("anselm");
        $this->assertEquals(0, $this->getConnection()->createQueryTable("users",
                               "SELECT id,name,admin FROM users WHERE name='anselm';")->getRowCount());

        $this->dbi->toggleAdminStatus("test");
        $this->assertEquals(1, $this->getConnection()->createQueryTable("testuser",
                               "SELECT admin FROM users WHERE name='test';")->getValue(0, "admin"));

        $this->dbi->toggleAdminStatus("test");
        $this->assertEquals(0, $this->getConnection()->createQueryTable("testuser",
                               "SELECT admin FROM users WHERE name='test';")->getValue(0, "admin"));
    }

    public function testUserSettings() {
        $this->assertEquals($this->expected["settings"]["test"],
                            $this->dbi->getUserSettings("test"));

        $this->dbi->setUserSettings("test", "50", "3");
        $this->assertEquals(50,
            $this->getConnection()->createQueryTable("settings",
            "SELECT lines_per_page FROM users WHERE name='test';")->getValue(0, "lines_per_page"));
        $this->assertEquals(3,
            $this->getConnection()->createQueryTable("settings",
            "SELECT lines_context FROM users WHERE name='test';")->getValue(0, "lines_context"));

        $this->dbi->setUserSetting("test", "columns_order", "7/6,6/7");
        $this->assertEquals("7/6,6/7",
            $this->getConnection()->createQueryTable("settings",
            "SELECT columns_order FROM users WHERE name='test';")->getValue(0, "columns_order"));
        $this->assertFalse($this->dbi->setUserSetting("test", "invalid_field", "somevalue"));

        // toggleNormStatus
        // isAllowedToDeleteFile($fid, $user)
        // isAllowedToOpenFile($fid, $user)
    }

    public function testTextQuery() {
        $actual = $this->dbi->queryForMetadata("sigle", "t1");
        $this->assertEquals($this->expected["texts"]["t1"], $actual);
        $actual = $this->dbi->queryForMetadata("fullname", "yet another dummy");
        $this->assertEquals($this->expected["texts"]["t2"], $actual);


        $this->assertEquals(array('file_id' => '3', 'file_name' => 'test-dummy'),
                            $this->dbi->getLockedFiles("bollmann"));

        $getfiles_expected = array();
        // getFiles also gives lots of names for display purposes
        foreach (array("t1","t2","t3") as $textkey) {
            $getfiles_expected[] = array_merge($this->expected["texts"][$textkey],
                                               $this->expected["texts_extended"][$textkey]);
        }
        $this->assertEquals($getfiles_expected, $this->dbi->getFiles());
        $this->assertEquals($getfiles_expected,
                            $this->dbi->getFilesForUser("bollmann"));

        $this->dbi->markLastPosition("3", "2");
        $this->assertEquals("2",
            $this->getConnection()->createQueryTable("currentpos",
            "SELECT currentmod_id FROM text WHERE id=3;")->getValue(0, "currentmod_id"));
    }

    public function testLockUnlock() {
        // locking a file that doesn't exist
        /* this returns { success: true, lockCount: 0 }
         * TODO find out if this is intended
        $lock_result = $this->dbi->lockFile("512", "test");
        $this->assertEquals(array("success" => false),
            $lock_result);
         */

        // locking a file that is already locked returns info on the lock
        $lock_result = $this->dbi->lockFile("3", "test");
        $this->assertEquals(array("success" => false,
                                  "lock" => array("2013-02-05 13:00:40",
                                                  "bollmann")),
                            $lock_result);
        // check if the database still has the lock belonging to bollmann
        $this->assertEquals("3",
            $this->getConnection()->createQueryTable("testlock",
            "SELECT user_id FROM locks WHERE text_id=3;")->getValue(0, "user_id"));


        // test force unlock with specification of user name
        $this->dbi->unlockFile("3", "bollmann", "true");
        $this->assertEquals("0",
            $this->getConnection()->createQueryTable("locks",
            "SELECT * FROM locks WHERE text_id=3;")->getRowCount());

        // test locking a new file
        $lock_result = $this->dbi->lockFile("4", "test");
        $this->assertEquals(array("success" => true, "lockCounts" => 0),
                            $lock_result);
        $this->assertEquals("4",
            $this->getConnection()->createQueryTable("testlock",
            "SELECT text_id FROM locks WHERE user_id=5;")->getValue(0, "text_id"));

        // test unlocking with force=false
        // fake login as bollmann
        $_SESSION["user_id"] = "3";
        // this should fail
        $lock_result = $this->dbi->unlockFile("4");
        $this->assertEquals("1",
            $this->getConnection()->createQueryTable("testlock",
            "SELECT * FROM locks WHERE text_id=4;")->getRowCount());

        // fake login as test
        $_SESSION["user_id"] = "5";
        // this should succeed
        $lock_result = $this->dbi->unlockFile("4");
        $this->assertEquals("0",
            $this->getConnection()->createQueryTable("testlock",
            "SELECT * FROM locks WHERE text_id=4;")->getRowCount());
    }

    public function testOpenText() {
        // test file opening
        $_SESSION["user"] = "bollmann";
        $_SESSION["user_id"] = "3";
        $this->assertEquals(
            array("lastEditedRow" => -1,
                  "data" => array_merge($this->expected["texts"]["t1"],
                                        array("tagset_id" => "1")),
                  "success" => true),
            $this->dbi->openFile("3")
        );

        $_SESSION["user"] = "test";
        $_SESSION["user_id"] = "5";
        $this->assertEquals(
            array("lastEditedRow" => 1,
                  "data" => array_merge($this->expected["texts"]["t2"],
                                        array("tagset_id" => "1")),
                  "success" => true),
            $this->dbi->openFile("4")
        );

        // opening a file that's already opened by someone else must fail
        $this->assertEquals(array("success" => false),
                            $this->dbi->openFile("3"));
    }
    public function testGetLines() {
        $lines_expected = $this->expected["lines"];

        $this->assertEquals($lines_expected,
                            $this->dbi->getLines("3", "0", "10"));

        $lines_chunk = array_chunk($lines_expected, 3);
        $this->assertEquals($lines_chunk[0],
                            $this->dbi->getLines("3", "0", "3"));
        $this->assertEquals($lines_chunk[1],
                            $this->dbi->getLines("3", "3", "3"));

        // querying over the maximum lines number gives an empty array
        $this->assertEquals(array(),
                            $this->dbi->getLines("3", "500", "10"));
        // querying a file that has no tokens gives an empty array as well
        $this->assertEquals(array(),
                            $this->dbi->getLines("5", "0", "10"));

        $this->assertEquals("9", $this->dbi->getMaxLinesNo("3"));
        $this->assertEquals("0", $this->dbi->getMaxLinesNo("5"));
        $this->assertEquals("0", $this->dbi->getMaxLinesNo("512"));

        //insertNewDocument($options, $data);
        //getAllSuggestions($fid, $line_id);
        //saveLines($fid, $lasteditedrow, $lines);
    }

    public function testProjects() {
        $this->assertEquals(array(
                                array('id' => '1', 'name' => 'Default-Gruppe')
                            ),
                            $this->dbi->getProjects());
        $this->assertEquals(array(array('project_id' => '1', 'username' => 'bollmann')),
                            $this->dbi->getProjectUsers());

        $this->assertEquals(array(array('id' => '1', 'name' => 'Default-Gruppe')),
                            $this->dbi->getProjectsForUser("bollmann"));

        $this->dbi->createProject("testproject");
        $expected = $this->createXMLDataSet("created_project.xml");

        $this->assertTablesEqual($expected->getTable("project"),
            $this->getConnection()->createQueryTable("project",
            "SELECT * FROM project WHERE name='testproject'"));

        $this->assertTrue($this->dbi->deleteProject("2"));
        $this->assertEquals("0",
            $this->getConnection()->createQueryTable("project",
            "SELECT * FROM project WHERE id=2")->getRowCount());

        // deleting a project that has users attached should fail
        // but that test is further down in the FK aware class
        */

        $users = array("test");
        $this->dbi->changeProjectUsers("1", $users);
        $this->assertEquals("1",
            $this->getConnection()->createQueryTable("projectusers",
            "SELECT * FROM user2project WHERE user_id=5 AND project_id=1")->getRowCount());
    }

    /*
    public function testGetAllLines() {
        $this->assertEquals($lines_expected,
                            $this->dbi->getAllLines("3"));
    }
     */

    public function testSaveLines() {
        //saveLines($fid, $lastedited, $lines);
        $_SESSION["user"] = "bollmann";
        $result = $this->dbi->saveLines("3", "9",
            array(
                array('id' => '2',
                      'anno_POS' => 'PPOSS',
                      'anno_morph' => 'Fem.Nom.Sg'),
                array('id' => '3',
                      'anno_POS' => 'VVFIN',
                      'anno_morph' => '3.Pl.Past.Konj'),
                array('id' => '4',
                      'anno_POS' => null),
                array('id' => '5',
                      'anno_POS' => 'VVPP',
                      'anno_lemma' => 'newlemma'),
                array('id' => '6',
                      'anno_norm' => 'newnorm'),
                array('id' => '7',
                      'anno_POS' => 'NN',
                      'anno_morph' => 'Neut.Dat.Pl',
                      'general_error' => false,
                      'anno_lemma' => null),
                array('id' => '8',
                      'anno_norm' => 'bla',
                      'general_error' => true),
                array('id' => '9',
                      'anno_morph' => 'Neut.Nom.Sg',
                      'anno_lemma' => 'blatest',
                      'anno_norm' => "")
            ));
        $this->assertFalse($result);
        $expected = $this->createXMLDataset("saved_lines.xml");
        $this->assertTablesEqual($expected->getTable("tag_suggestion"),
            $this->getConnection()->createQueryTable("tag_suggestion",
             "SELECT id,selected,source,tag_id,mod_id "
            ."FROM tag_suggestion WHERE mod_id > 2 and mod_id < 9"));
        $this->assertTablesEqual($expected->getTable("tag"),
            $this->getConnection()->createQueryTable("tag",
            "SELECT * FROM tag WHERE id > 509"));
        $this->assertTablesEqual($expected->getTable("mod2error"),
            $this->getConnection()->createQueryTable("mod2error",
            "SELECT * FROM mod2error WHERE mod_id IN (7, 8, 9)"));
    }

    /*
    public function testDeleteFile() {
        $this->dbi->deleteFile("3");
        // TODO of course it needs to test if the tokens, etc. are also
        // deleted, but cora relies on fk constraints for that, which are
        // ignored in our test db
        $this->assertEquals(0,
            $this->query("SELECT * FROM {$GLOBALS["DB_DBNAME"]}.text WHERE ID=3")->getRowCount());
    }*/

}

/** Tests that need FK awareness.
 *
 * TODO this needs to be moved to a separate file,
 * since apparently phpunit doesn't allow more than one test
 * class in a file.
 */
class Cora_Tests_DBInterface_FK_test extends Cora_Tests_DbTestCase_FKAware {
    protected $dbi;
    protected $backupGlobalsBlacklist = array('_SESSION');

    protected function setUp() {
        $this->dbi = new DBInterface($this);
        parent::setUp();
    }

    public function testDeleteProjectWithUsers() {
        $this->assertFalse($this->dbi->deleteProject("1"));
        $this->assertEquals("1",
            $this->getConnection()->createQueryTable("project",
            "SELECT * FROM project WHERE id=1")->getRowCount());
        $this->fail();
    }
}


?>