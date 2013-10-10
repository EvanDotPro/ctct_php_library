<?php

class Ctct_ListIterator extends Ctct_AbstractIterator
{
    protected $_action = 'lists';

    public function next()
    {
        parent::next();
        $this->_skipBuiltInLists();
    }

    public function rewind()
    {
        parent::rewind();
        $this->_skipBuiltInLists();
    }

    protected function _skipBuiltInLists()
    {
        // skip built-in Constant Contact lists
        $ignored = array('Active', 'Removed', 'Do Not Mail');
        if (in_array($this->current()->name, $ignored)) {
            $this->next();
        }
    }

    protected function _populateCurrent($current)
    {
        $currentList = new Ctct_Model_List();
        $currentList->link = (string) $current->link->Attributes()->href;
        $currentList->id = (string) $current->id;
        $currentList->name = (string) $current->content->ContactList->Name;
        $currentList->contactCount = (string) $current->content->ContactList->ContactCount;

        return $currentList;
    }
}
