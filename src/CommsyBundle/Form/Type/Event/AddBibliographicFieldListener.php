<?php
namespace CommsyBundle\Form\Type\Event;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use CommsyBundle\Form\Type\Bibliographic\BiblioArticleType;
use CommsyBundle\Form\Type\Bibliographic\BiblioBookType;
use CommsyBundle\Form\Type\Bibliographic\BiblioChapterType;
use CommsyBundle\Form\Type\Bibliographic\BiblioCollectionType;
use CommsyBundle\Form\Type\Bibliographic\BiblioDocManagementType;
use CommsyBundle\Form\Type\Bibliographic\BiblioJournalType;
use CommsyBundle\Form\Type\Bibliographic\BiblioManuscriptType;
use CommsyBundle\Form\Type\Bibliographic\BiblioNewspaperType;
use CommsyBundle\Form\Type\Bibliographic\BiblioPictureType;
use CommsyBundle\Form\Type\Bibliographic\BiblioPlainType;
use CommsyBundle\Form\Type\Bibliographic\BiblioThesisType;
use CommsyBundle\Form\Type\Bibliographic\BiblioWebsiteType;
use CommsyBundle\Form\Type\Bibliographic\BiblioNoneType;


/**
* 
*/
class AddBibliographicFieldListener implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'onPreSetData',
            FormEvents::POST_SUBMIT => 'onPostSubmit',
            FormEvents::PRE_SUBMIT => 'onPreSubmit'
        );
    }

    public function onPostSubmit(FormEvent $event)
    {
        $event->stopPropagation();
    }

    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (isset($data['biblio_select']) && $data['biblio_select'] != 'none') {
            // $form->add('biblio_sub', new BiblioPlainType());
        
            $bibNamespace = 'CommsyBundle\Form\Type\Bibliographic';
            // $class = $bibNamespace.'\Biblio'.ucfirst($data['biblio_select']).'Type';
            if (!empty($data['biblio_select'])) {
                $class = $bibNamespace.'\\'.$data['biblio_select'];
                $form->add('biblio_sub', $class);
            } else {
                $class = $bibNamespace.'\\'.'BiblioNoneType';
                $form->add('biblio_sub', $class);
            }
            

        }
    }

    public function onPreSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (isset($data['biblio_select']) && $data['biblio_select'] != 'none') {
            $bibNamespace = 'CommsyBundle\Form\Type\Bibliographic';
            
            if (!empty($data['biblio_select']) && $data['biblio_select'] != 'BiblioType') {
                $class = $bibNamespace.'\\'.$data['biblio_select'];
                $form->add('biblio_sub', $class);
            } else {
                $class = $bibNamespace.'\\'.'BiblioNoneType';
                $form->add('biblio_sub', $class);
            }

        }
    }
}