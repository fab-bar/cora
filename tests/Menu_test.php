<?php
require_once"../lib/contentModel.php";

/** Test Menu class
 *  02/2013 Florian Petran
 */
class Cora_Tests_Menu_Test extends PHPUnit_Framework_TestCase {
    protected $menu;
    protected $test_data;

    public function setUp() {
        $this->test_data = array(
            "item1" => array(
                        "id" => "1",
                        "file" => "testfile.php",
                        "js_file" => "testfile.js",
                        "caption" => "Test Caption",
                        "tooltip" => "Test Tooltip"
            ),
            "item2" => array(
                        "id" => "2",
                        "file" => "test2.php",
                        "js_file" => "test2.js",
                        "caption" => "Another Test Caption",
                        "tooltip" => "Another Test Tooltip"
            )
        );
        $this->menu = new Menu();
    }

    public function testAddItem() {
        $this->menu->addMenuItem(
            $this->test_data["item1"]["id"],
            $this->test_data["item1"]["file"],
            $this->test_data["item1"]["js_file"],
            $this->test_data["item1"]["caption"],
            $this->test_data["item1"]["tooltip"]
        );

        // assert that the menu item is the one we just added
        $this->assertEquals(array($this->test_data["item1"]["id"]),
            $this->menu->getItems());
        $this->assertEquals($this->test_data["item1"]["file"],
            $this->menu->getItemFile($this->test_data["item1"]["id"]));
        $this->assertEquals($this->test_data["item1"]["js_file"],
            $this->menu->getItemJSFile($this->test_data["item1"]["id"]));
        $this->assertEquals($this->test_data["item1"]["tooltip"],
            $this->menu->getItemTooltip($this->test_data["item1"]["id"]));
        $this->assertEquals($this->test_data["item1"]["caption"],
            $this->menu->getItemCaption($this->test_data["item1"]["id"]));
    }

    public function testDefaultItem() {
        foreach ($this->test_data as $item) {
            $this->menu->addMenuitem(
                $item["id"],
                $item["file"],
                $item["js_file"],
                $item["caption"],
                $item["tooltip"]
            );
        }

        $this->assertEquals($this->test_data["item1"]["id"],
                            $this->menu->getDefaultItem());

        $this->menu->setDefaultItem($this->test_data["item2"]["id"]);
        $this->assertEquals($this->test_data["item2"]["id"],
                            $this->menu->getDefaultItem());
    }
}
?>
