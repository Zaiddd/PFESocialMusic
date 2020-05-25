<?php


namespace App\Controller;


use App\Entity\CommentPublication;
use App\Entity\Publication;
use App\Entity\Signal;
use App\Entity\User;
use App\Form\CommentPublicationForm;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class SignalController extends AbstractController
{

    /**
     * @Route("/{id}/signaler", name="Publication.Signaler", methods={"GET","POST"})
     */
    public function newSignal(Request $request, UserPasswordEncoderInterface $encoder, ManagerRegistry $doctrine, $id): Response
    {
        $idPubli = $doctrine->getRepository(Publication::class)->findOneBy(['id' => [$id]]);
        $idUser = $doctrine->getRepository(User::class)->find($this->getUser());
        $publication = $doctrine->getRepository(Publication::class)->findAll();

        $signal = new Signal();
        $signal->setPublication($idPubli);
        $signal->setNomUser($idUser->getPseudo());
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($signal);
        $entityManager->flush();

        return $this->redirectToRoute('User.accueil', [
            'publication' => $publication
        ]);
    }

    /**
     * @Route("users/{id}/signaler", name="Publication.SignalerFromSearch", methods={"GET","POST"})
     */
    public function newSignalFromSearch(Request $request, UserPasswordEncoderInterface $encoder, ManagerRegistry $doctrine, $id): Response
    {
        $idPubli = $doctrine->getRepository(Publication::class)->findOneBy(['id' => [$id]]);
        $idUser = $doctrine->getRepository(User::class)->find($this->getUser());
        $publication = $doctrine->getRepository(Publication::class)->findAll();

        $signal = new Signal();
        $signal->setPublication($idPubli);
        $signal->setNomUser($idUser->getPseudo());
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($signal);
        $entityManager->flush();

        return $this->redirectToRoute('User.accueil', [
            'publication' => $publication
        ]);
    }

    /**
     * @Route("/signals", name="Admin.ShowSignals", methods={"GET"})
     */
    public function index(UserRepository $userRepository, EntityManagerInterface $doctrine): Response
    {
        $signals = $doctrine->getRepository(Signal::class)->findAll();
        return $this->render('admin/signals.html.twig', [
            'signals' => $signals
        ]);
    }

    /**
     * @Route("/publicationSignalee/{id}", name="Admin.ShowPubliSignalee", methods={"GET"})
     */
    public function showPubliSignalee(UserRepository $userRepository, EntityManagerInterface $doctrine, $id): Response
    {
        $idPubli = $doctrine->getRepository(Publication::class)->findOneBy(['id' => [$id]]);
        $signals = $doctrine->getRepository(Signal::class)->findOneBy(['publication' =>$idPubli]);
        $user = $doctrine->getRepository(User::class)->findOneBy(['pseudo' => $signals->getNomUser()]);
        $publication = $doctrine->getRepository(Publication::class)->findOneBy(['user' => $user]);
        $commentaires = $doctrine->getRepository(CommentPublication::class)->findOneBy(['id' => $publication]);

        return $this->render('publication/showOnePubli.html.twig', [
            'signals' => $signals,
            'user' => $user,
            'publication' => $publication,
            'commentaires' => $commentaires
        ]);
    }

    /**
     * @Route("deleteSignal/{id}", name="Admin.deleteSignal")
     */
    public function deleteSignal(ManagerRegistry $doctrine, Request $request, $id)
    {
        $signal = $doctrine->getRepository(Signal::class)->findOneBy(['id' => [$id]]);

        $entityManager = $this->getDoctrine()->getManager();
        $this->addFlash('success', 'Signalement terminé !');

        $entityManager->remove($signal);

        $entityManager->flush();

        return $this->redirectToRoute('Admin.ShowSignals');
    }

}