<?php

// copyright 2019 Philippe Logel All rights reserved not MIT licence
use Slim\Http\Request;
use Slim\Http\Response;

// Documents filemanager APIs
use EcclesiaCRM\Note;
use EcclesiaCRM\NoteQuery;
use EcclesiaCRM\SessionUser;
use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\PersonQuery;

$app->group('/document', function () {
    
    $this->post('/create', 'createDocument' );
    $this->post('/get', 'getDocument' );
    $this->post('/update', 'updateDocument' );
    $this->post('/delete', 'deleteDocument' );
    $this->post('/leave', 'leaveDocument' );

});

function createDocument(Request $request, Response $response, array $args) {
  $input = (object)$request->getParsedBody();

  if ( isset ($input->personID) && isset ($input->famID) && isset ($input->title) && isset ($input->type) && isset ($input->text) && isset ($input->bPrivate)){
    $note = new Note();
    $note->setPerId($input->personID);
    $note->setFamId($input->famID);
    $note->setPrivate($input->bPrivate);
    $note->setTitle($input->title);
    $note->setText($input->text);
    $note->setType($input->type);
    $note->setEntered(SessionUser::getUser()->getPersonId());
    $note->setDateLastEdited(new DateTime());

    $note->setCurrentEditedBy(0);
    $note->setCurrentEditedDate(NULL);
    
    $note->save();
   
    return $response->withJson(['success' => true]);
  }
  
  return $response->withJson(['success' => false]);
}

function getDocument(Request $request, Response $response, array $args) {
  $input = (object)$request->getParsedBody();

  if ( isset ($input->docID) && isset ($input->personID) && isset ($input->famID) ){
    $note = NoteQuery::Create()->findOneById ($input->docID);
    
    if ( $note->getCurrentEditedBy() > 0 && !( SessionUser::getUser()->isAdmin() || $note->isVisualableBy ($input->personID) ) ) {
      $currentDate = new DateTime();
    
      $since_start = $currentDate->diff($note->getCurrentEditedDate());
      
      $min = $since_start->days * 24 * 60;
      $min += $since_start->h * 60;
      $min += $since_start->i;
      
      if ( $min < SystemConfig::getValue('iDocumentTimeLeft') ) {
        $editor = PersonQuery::create()->findPk($note->getCurrentEditedBy());
        if ($editor != null) {
            $currentUserName = gettext("This document is opened by")." : ".$editor->getFullName()." (".(SystemConfig::getValue('iDocumentTimeLeft')-$min)." ".gettext("Minutes left").")";
        }
        
        return $response->withJson(['success' => false ,'note' => $note->toArray(),'message' => $currentUserName]);
        
      } else {// we reset the count
         $note->setCurrentEditedDate(null);
         $note->setCurrentEditedBy(0);
         $note->save();
      }
    } else {
    
      // now the document is used by someone
      $note->setCurrentEditedBy(SessionUser::getUser()->getPersonId());
      $note->setCurrentEditedDate(new DateTime());
          
      $note->save();
      // !now the document is used by someone
      
    }
       
    return $response->withJson(['success' => true ,'note' => $note->toArray()]);
  }
  
  return $response->withJson(['success' => false]);
}

function leaveDocument(Request $request, Response $response, array $args) {
  $input = (object)$request->getParsedBody();

  if ( isset ($input->docID) ){
    $note = NoteQuery::Create()->findOneById ($input->docID);
    
    if (!is_null($note)) {
    
      // now the document is used by someone
      $note->setCurrentEditedBy(0);
      $note->setCurrentEditedDate(NULL);
          
      $note->save();
      // !now the document is used by someone
      
      return $response->withJson(['success' => true ,'note' => $note->toArray()]);
    }
       
    return $response->withJson(['success' => true]);
  }
  
  return $response->withJson(['success' => false]);
}

function updateDocument(Request $request, Response $response, array $args) {
  $input = (object)$request->getParsedBody();

  if ( isset ($input->docID) && isset ($input->title) && isset ($input->type) && isset ($input->text) && isset ($input->bPrivate)){
    $note = NoteQuery::Create()->findOneById ($input->docID);
    
    $note->setPrivate($input->bPrivate);
    $note->setTitle($input->title);
    $note->setText($input->text);
    $note->setType($input->type);
    $note->setEntered(SessionUser::getUser()->getPersonId());
    $note->setDateLastEdited(new DateTime());
    
    // now the document is no more used
    $note->setCurrentEditedBy(0);
    $note->setCurrentEditedDate(NULL);
    // ! now the document is no more used
          
    $note->save();
   
    return $response->withJson(['success' => true,'note' => $note->toArray()]);
  }
  
  return $response->withJson(['success' => false]);
}


function deleteDocument(Request $request, Response $response, array $args) {
  $input = (object)$request->getParsedBody();

  if ( isset ($input->docID) ){
    $note = NoteQuery::Create()->findOneById ($input->docID);
    
    $note->delete();
   
    return $response->withJson(['success']);
  }
  
  return $response->withJson(['success' => false]);
}