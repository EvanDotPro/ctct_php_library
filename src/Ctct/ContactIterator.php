<?php

class Ctct_ContactIterator extends Ctct_AbstractIterator
{
    protected $_action = 'contacts';

    public function setFilter($updatedSince, $listId = null, $activeOnly = false)
    {
        $params = array('updatedsince' => $updatedSince);
        if ($listId) $params['listid'] = $listId;
        if ($activeOnly) $params['listtype'] = 'active';

        $this->_action = 'contacts?' . http_build_query($params);

        return $this;
    }

    protected function _populateCurrent($current)
    {
        $contact = new Ctct_Model_Contact();
        $contact->id = (string) $current->id;
        $contact->link = (string) $current->link->Attributes()->href;
        $contact->name = (string) $current->content->Contact->Name;
        $contact->firstName = (string) $current->content->Contact->FirstName;
        $contact->lastName = (string) $current->content->Contact->LastName;
        $contact->email = (string) $current->content->Contact->EmailAddress;
        $contact->homePhone = (string) $current->content->Contact->HomePhone;
        $contact->workPhone = (string) $current->content->Contact->WorkPhone;
        $contact->confirmed = (string) $current->content->Contact->Confirmed;//todo boolean
        $contact->lastUpdateTime = (string) $current->content->Contact->LastUpdateTime; //todo datetime

        return $contact;
    }
}
