<?php


namespace App\Controller;


use App\Form\CommentPublicationForm;
use App\Form\SearchForm;
use App\Form\SearchPubliForm;
use App\Repository\PublicationRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\CommentPublication;
use App\Entity\Publication;
use App\Entity\User;
use App\Form\GoutsUserForm;
use App\Form\UserRegisterForm;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Security\Core\User\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Validator\Constraints\DateTime;
use Twig\Environment;
use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SearchController extends AbstractController
{

    /**
     * @Route("/recherche", name="User.search")
     */
    public function recherche(Request $request, UserRepository $repo, PaginatorInterface $paginator) {

        $searchForm = $this->createForm(SearchForm::class);
        $searchForm->handleRequest($request);

        $donnees = $repo->findAll();

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {

            $pseudo = $searchForm->getData()->getPseudo();

            $donnees = $repo->search($pseudo);


            if ($donnees == null) {
                $this->addFlash('erreur', 'Aucun utilisateur contenant ce mot clé dans son pseudo n\'a été trouvé, essayez en un autre.');

            }

        }

        // Paginate the results of the query
        $users = $paginator->paginate(
        // Doctrine Query, not results
            $donnees,
            // Define the page parameter
            $request->query->getInt('page', 1),
            // Items per page
            4
        );

        return $this->render('search/search.html.twig',[
            'users' => $users,
            'searchForm' => $searchForm->createView()
        ]);
    }

    /**
     * @Route("/users/{id}", name="Recherche.showProfil")
     */
    public function showProfilUSER(ManagerRegistry $doctrine, $id)
    {
        $user = $doctrine->getRepository(User::class)->findOneBy(['id' => [$id]]);
        $publication = $doctrine->getRepository(Publication::class)->findBy(['user' => $user]);
        return $this->render('search/showProfilSearch.html.twig', [
            'user' => $user,
            'publication' => $publication
        ]);
    }

    /**
     * @Route("/users/{id}/commenter", name="User.commentPubliSearch", methods={"GET","POST"})
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
        $userCommente = $doctrine->getRepository(User::class)->find($this->getUser());

        $arrayComment = $idPubli->getReponses();
        $arrayUser = $idPubli->getUserQuiCommente();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            $com = $form->get('newComment')->getData();
            array_push($arrayComment, $com);
            array_push($arrayUser, $userCommente);
            $idPubli->setUserQuiCommente($arrayUser);
            $idPubli->setReponses($arrayComment);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($newComment);
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire ajouté !');
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
     * @Route("/search/optionsMenu", name="Search.Options", methods={"GET","POST"})
     */
    public function choixRegister(Request $request, UserPasswordEncoderInterface $encoder): Response
    {
        return $this->render('search/searchOptions.html.twig');
    }

    /**
     * @Route("/recherchePubli", name="User.searchPubli")
     */
    public function recherchePubli(Request $request, PublicationRepository $repo, PaginatorInterface $paginator) {

        $searchForm = $this->createForm(SearchPubliForm::class);
        $searchForm->handleRequest($request);

        $donnees = $repo->findAll();

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {

            $tags = $searchForm->getData()->getTags();

            $donnees = $repo->search($tags);


            if ($donnees == null) {
                $this->addFlash('erreur', 'Aucune publication comportant ce tag saisi. Réessayez un autre.');

            }

        }

        // Paginate the results of the query
        $publication = $paginator->paginate(
        // Doctrine Query, not results
            $donnees,
            // Define the page parameter
            $request->query->getInt('page', 1),
            // Items per page
            4
        );

        return $this->render('search/searchPubli.html.twig',[
            'publication' => $publication,
            'searchForm' => $searchForm->createView()
        ]);
    }
}