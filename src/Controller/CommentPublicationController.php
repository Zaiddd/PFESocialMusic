<?php


namespace App\Controller;


use App\Entity\CommentPublication;
use App\Entity\Publication;
use App\Entity\User;
use App\Form\CommentPublicationForm;
use App\Form\PublicationForm;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Routing\Annotation\Route;

class CommentPublicationController extends AbstractController
{

    /**
     * @Route("/{id}/commenter", name="User.commenterPublication", methods={"GET","POST"})
     */
    public function new(Request $request, UserPasswordEncoderInterface $encoder, ManagerRegistry $doctrine): Response
    {
        $idPubli = $doctrine->getRepository(Publication::class)->find((int)$request->attributes->get('id'));
        $id = $doctrine->getRepository(User::class)->find($this->getUser()->getId());
        $publication = $doctrine->getRepository(Publication::class)->findAll();
        $newComment = new CommentPublication();

        $form = $this->createForm(CommentPublicationForm::class, $newComment)
            ->add('save', SubmitType::class, ['label' => 'Commenter']);

        $newComment->setIdPublication($idPubli);
        $userCommente = $this->getUser();

        $arrayComment = $idPubli->getReponses();
        $arrayUser = $idPubli->getUserQuiCommente();


        $newComment->setDate(new \DateTime());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            $com = $form->get('newComment')->getData();

            array_push($arrayComment, $com);
            array_push($arrayUser, $userCommente);
            $idPubli->setUserQuiCommente($arrayUser);
            $idPubli->setReponses($arrayComment);
            foreach($publication as $unePubli){
                if($unePubli->getIdUserOriginal() == $idPubli->getIdUserOriginal()){
                    $arrayComment2 = $unePubli->getReponses();
                    $arrayUser2 = $unePubli->getUserQuiCommente();
                    array_push($arrayComment2, $com);
                    array_push($arrayUser2, $userCommente);
                    $unePubli->setUserQuiCommente($arrayUser2);
                    $unePubli->setReponses($arrayComment2);
                }
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($newComment);
            $entityManager->flush();
            return $this->redirectToRoute('User.accueil', [
                'publication' => $publication
            ]);
        }

        return $this->render('publication/commenter.html.twig', [
            'idPubli' => $idPubli,
            'newComment' => $newComment,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id2}/{id}/deleteCommentaire", name="Commentaire.delete")
     */
    public function deleteCommentaire(ManagerRegistry $doctrine, Request $request, $id, $id2)
    {
        $idPubli = $doctrine->getRepository(Publication::class)->findOneBy(['id' => [$id2]]);
        $idComment = $doctrine->getRepository(CommentPublication::class)->findOneBy(['newComment' => [$id]]);
        $entityManager = $this->getDoctrine()->getManager();

        $arrayCommentairesPubli = $idPubli->getReponses();
        array_splice($arrayCommentairesPubli, array_search($idComment->getNewComment(),$arrayCommentairesPubli),1);
        $idPubli->setReponses($arrayCommentairesPubli);
        $entityManager->remove($idComment);


        $entityManager->flush();

        return $this->redirectToRoute('User.accueil');
    }

}