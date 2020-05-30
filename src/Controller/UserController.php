<?php


namespace App\Controller;



use App\Entity\Banned;
use App\Entity\CommentPublication;
use App\Entity\Publication;
use App\Entity\Signal;
use App\Entity\User;
use App\Form\GoutsUserForm;
use App\Form\UserRegisterForm;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FOS\UserBundle\Model\UserManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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

class UserController extends AbstractController
{

    private $userManager;

    public function __construct(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @Route("/userAccueil", name="User.accueil", methods={"GET"})
     * @IsGranted("ROLE_USER")
     */
    public function index(UserRepository $userRepository, EntityManagerInterface $doctrine): Response
    {
        $publication = $doctrine->getRepository(Publication::class)->findAll();
        $banned = $doctrine->getRepository(Banned::class)->find(1);
        $signals = $doctrine->getRepository(Signal::class)->findAll();
        $allUsers = $doctrine->getRepository(User::class)->findAll();
        $user = $doctrine->getRepository(User::class)->findOneBy(['id' => $this->getUser()->getId()]);
        $commentaires = $doctrine->getRepository(CommentPublication::class)->findOneBy(['id' => $publication]);
        return $this->render('user/accueilUser.html.twig', [
            'banned' => $banned,
            'signals' => $signals,
            'allUsers' => $allUsers,
            'user' => $user,
            'publication' => $publication,
            'commentaires' => $commentaires
        ]);
    }

    /**
     * @Route("/listeUsers", name="User.ListeUsers", methods={"GET"})
     */
    public function showListeUsers(UserRepository $userRepository, EntityManagerInterface $doctrine): Response
    {
        $publication = $doctrine->getRepository(Publication::class)->findAll();
        $users = $doctrine->getRepository(User::class)->findAll();
        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'publication' => $publication,
        ]);
    }

    /**
     * @Route("/user/menuInscription", name="User.choixRegister", methods={"GET","POST"})
     */
    public function choixRegister(Request $request, UserPasswordEncoderInterface $encoder): Response
    {
        return $this->render('login/registerMenu.html.twig');
    }

    /**
     * @Route("/user/inscription", name="User.ajouter", methods={"GET","POST"})
     */
    public function new(Request $request, UserPasswordEncoderInterface $encoder): Response
    {

        $user = new User();
        $token = $this->genererTokenMail();
        $user->setEstActive(0);
        $user->setMailTokenVerification($token);
        $user->setEnabled(true);

        $user->setPlainPassword("your chosen password");
        $form = $this->createForm(UserRegisterForm::class, $user)
            ->add('confirmMdp', PasswordType::class,['label' => 'Confirmez', "mapped"=>false])
            ->add('save', SubmitType::class, ['label' => 'Créer un compte']);
        $form->handleRequest($request);
        $passwordsmatch = strcmp($form->get("mdp")->getData(),$form->get("confirmMdp")->getData())==0;
        if( !$passwordsmatch ){
            $form->get('confirmMdp')->addError(new FormError('Les deux mots de passes ne correspondent pas'));
        }
        $dateNaissance = $user->getDateNaissance();
        $dateActuelle = new \DateTime(date('Y-m-d'));
        $dateMajorite = date_sub($dateActuelle, date_interval_create_from_date_string('18 years'));
        $majeur = true;
        if ( $dateNaissance > $dateMajorite ) {
            $form->get('dateNaissance')->addError(new FormError('Il faut être majeur pour s\'insrire'));
            $majeur = false;
        }
        $exists = $this->getDoctrine()->getManager()->getRepository(User::class)->findOneBy(['mail' => $form->get('mail')->getData()]) != null;
        if($exists)
            $form->get('mail')->addError(new FormError("Cette adresse mail est déja utilisée"));

        if ($form->isSubmitted() && $form->isValid() && $passwordsmatch && $majeur && !$exists)
        {
            $user->setCouleurFond('#4ecdc4');
            $user->setCouleurMenu('#7bdff2');
            $user->setEstBanni(0);
            $user->setEmail($form->get('mail')->getData());
            $user->setUsername($form->get('pseudo')->getData());
            $hash = $encoder->encodePassword($user, $form->get('mdp')->getData());
            $user->setMdp($hash);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->sendConfirmationEmail($form->get('mail')->getData(),$token);
            return $this->render('confirm.html.twig', [
                'mail' => $form->get('mail')->getData()
            ]);

        }

        return $this->render('user/newUser.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    public function genererTokenMail(){
        $token = "0123456789ABCDEF0123456789ABCDEF";
        $token = str_shuffle($token);
        $token = substr($token,strlen($token)/2);
        //$token = substr($token,-18);
        return $token;
    }

    public function sendConfirmationEmail($email,$token) {
        // Instantiation and passing `true` enables exceptions
        $mail = new PHPMailer(true);

        //Server settings
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host = $_SERVER['HOTE'];                    // Set the SMTP server to send through
        $mail->SMTPAuth = $_SERVER['SMTPAuth'];                                   // Enable SMTP authentication
        $mail->Username = $_SERVER['Username'];                     // SMTP username
        $mail->Password = $_SERVER['Password'];                               // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $mail->Port = $_SERVER['Port'];                                    // TCP port to connect to

        //Recipients
        $mail->setFrom('socialmusic25@gmail.com', 'Activation du compte SocialMusic');
        $mail->addAddress($email);     // Add a recipient

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Activation compte';
        $mail->Body = "<br><a href='localhost:8000/user/activer/" . $token . "'></a>Veuillez cliquer sur le lien pour activer votre compte : http://localhost:8000/user/activer/".$token ;

        $mail->send();
    }

    /**
     * @Route("/user/activer/{token}", name="User.activer")
     */
    public function activationUser(EntityManagerInterface $manager, $token)
    {
        $user = $this->getDoctrine()->getManager()->getRepository(User::class)->findOneBy(['mailTokenVerification' => $token]);
        $user->setEstActive(1);

        $dateMAJ = new \DateTime();
        /*
        $membre = new MembreFamille();
        $membre
            ->setNom($representant->getNom())
            ->setPrenom($representant->getPrenom())
            ->setDateNaissance($representant->getDateNaissance())
            ->setCategorie('Majeur')
            ->setNoClient($representant->getId())
            ->setTraitementDonnees(0)
            ->setDateMAJ($dateMAJ)
            ->setRepresentantFamille($representant)
            ->setReglementActivite(0);

        $info_majeur = new InformationMajeur();
        $info_majeur->setMail($representant->getMail());
        $info_majeur->setCommunicationResponsableLegal(0);
        $membre->setInformationMajeur($info_majeur);


        $manager->persist($membre);
        $manager->persist($info_majeur);
        */
        $manager->persist($user);
        $manager->flush();

        return $this->render('user/activation.html.twig', array(
            'user' => $user
        ));
    }

    /**
     * @Route("/user/reinitialiser", name="User.demandeResetPassword", methods={"GET","POST"})
     */
    public function demandeResetPassword(Request $request, UserPasswordEncoderInterface $encoder): Response
    {
        if($request->isMethod('GET')) {
            return $this->render('security/demandeResetPassword.html.twig');
        }
        dump($request->get('mail'));
        $entityManager = $this->getDoctrine()->getManager();
        $user = $this->getDoctrine()->getManager()->getRepository(User::class)->findOneBy(['mail' => $request->get('mail')]);
        if($user == null) {
            return $this->render('security/demandeResetPassword.html.twig', ['erreur' => 'Cette adresse mail n\'existe pas']);
        }
        if(!$user->getEstActive()){
            return $this->render('security/demandeResetPassword.html.twig', ['erreur' => 'Veuillez d\'abord activer le compte depuis votre boite mail']);
        }

        $mail = new PHPMailer(true);
        //Generation d'un token
        $token = $this->genererTokenMail();
        $user->setMailTokenVerification($token);
        $entityManager->flush();

        //Server settings
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host = $_SERVER['HOTE'];                    // Set the SMTP server to send through
        $mail->SMTPAuth = $_SERVER['SMTPAuth'];                                   // Enable SMTP authentication
        $mail->Username = $_SERVER['Username'];                     // SMTP username
        $mail->Password = $_SERVER['Password'];                               // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $mail->Port = $_SERVER['Port'];                                    // TCP port to connect to

        //Recipients
        $mail->setFrom('socialmusic25@gmail.com', 'Activation compte Social Music');
        $mail->addAddress($request->get('mail'));     // Add a recipient

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Reinitialisation du mot de passe';
        $mail->Body = "<br><a href='localhost:8000/user/reinitialiser/" . $token . "'></a>Veuillez cliquer sur ce lien pour activer votre compte http://localhost:8000/user/reinitialiser/".$token ;

        $mail->send();
        return $this->render('security/demandeResetPassword.html.twig', ['retour' => 'Un mail avec un lien de reinitialisation du mot de passe à été envoyé à '.$request->get('mail')]);
    }

    /**
     * @Route("/user/reinitialiser/{token}", name="User.reinitialiserMotDePasse", methods={"GET","POST"})
     */
    public function reinitialiserMotDePasse(Request $request, UserPasswordEncoderInterface $encoder, $token): Response
    {
        $user = $this->getDoctrine()->getManager()->getRepository(User::class)->findOneBy(['mailTokenVerification' => $token]);
        if($user == null){
            return $this->render('security/nouveauMotDePasse.html.twig',['retour'=>'Token invalide']);
        }
        if($request->isMethod('GET')) {
            return $this->render('security/nouveauMotDePasse.html.twig',['mail'=>$user->getMail()]);
        }
        if(strcmp($request->get("mdp1"),$request->get("mdp2"))!=0) {
            return $this->render('security/nouveauMotDePasse.html.twig',['mail'=>$user->getMail(),'erreur'=>'Les deux mot de passes ne correspondent pas']);
        }

        $hash = $encoder->encodePassword($user, $request->get('mdp1'));
        $user->setMdp($hash);
        $user->setMailTokenVerification("");
        $this->getDoctrine()->getManager()->flush();
        return $this->render('security/nouveauMotDePasse.html.twig',['retour'=>'Le mot de passe de '.$user->getMail().' a été modifié vous pouvez désormais vous connecter avec votre nouveau mot de passe']);
    }

    /**
     * @Route("/user/informationsPerso", name="User.InfosPerso.show")
     * @IsGranted("ROLE_USER")
     */
    public function showInformationsPersoUSER(ManagerRegistry $doctrine)
    {
        $user = $doctrine->getRepository(User::class)->findOneBy(['id' => $this->getUser()->getId()]);
        return $this->render('user/showInfosPerso.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @Route("/user/informationsPerso/edit", name="User.InfosPerso.edit", methods={"GET","POST"})
     * @IsGranted("ROLE_USER")
     */
    public function editInformationsPersoUSER(Request $request, ManagerRegistry $doctrine)
    {
        $user = $doctrine->getRepository(User::class)->find($this->getUser()->getId());
        $form = $this->createForm(UserRegisterForm::class, $user);
        $form->handleRequest($request);

        if ( $form->isSubmitted() && $form->isValid() )
        {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Informations personnelles modifiées ! ');

            return $this->redirectToRoute('User.InfosPerso.show');
        }

        return $this->render('user/editInfosUser.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/listeUsers/{id}/editUser", name="Admin.EditUser", methods={"GET","POST"})
     */
    public function editUserAdmin(Request $request, ManagerRegistry $doctrine, $id)
    {
        $user = $doctrine->getRepository(User::class)->findOneBy(['id' => [$id]]);
        $form = $this->createForm(UserRegisterForm::class, $user);
        $form->handleRequest($request);

        if ( $form->isSubmitted() && $form->isValid() )
        {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Informations personnelles modifiées ! ');

            return $this->redirectToRoute('User.ListeUsers');
        }

        return $this->render('user/editInfosUser.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/user/informationsPerso/editGouts", name="User.InfosPerso.editGouts", methods={"GET","POST"})
     * @IsGranted("ROLE_USER")
     */
    public function editGoutsUSER(Request $request, ManagerRegistry $doctrine)
    {
        $user = $doctrine->getRepository(User::class)->find($this->getUser()->getId());
        $form = $this->createForm(GoutsUserForm::class, $user);
        $form->handleRequest($request);

        if ( $form->isSubmitted() && $form->isValid() )
        {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Goûts musicaux mis à jour ! ');

            return $this->redirectToRoute('User.InfosPerso.show');
        }

        return $this->render('user/goutMusique.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/profil", name="User.showProfil")
     * @IsGranted("ROLE_USER")
     */
    public function showProfilUSER(ManagerRegistry $doctrine)
    {
        $publication = $doctrine->getRepository(Publication::class)->findAll();
        $user = $doctrine->getRepository(User::class)->findOneBy(['id' => $this->getUser()->getId()]);
        return $this->render('user/showProfil.html.twig', [
            'user' => $user,
            'publication' => $publication
        ]);
    }

    /**
     * @Route("/listeUsers/{id}/bannir", name="Admin.BanUser")
     */
    public function banUserAdmin(UserRepository $userRepository, EntityManagerInterface $doctrine, $id): Response
    {
        $users = $doctrine->getRepository(User::class)->findAll();
        $banned = $doctrine->getRepository(Banned::class)->find(1);
        $user = $doctrine->getRepository(User::class)->findOneBy(['id' => [$id]]);
        $user->setEstBanni(1);
        $banned->setNbBannis($banned->getNbBannis() + 1);
        $this->userManager->updateUser($user);
        $this->addFlash('success', 'Utilisateur banni !');

        return $this->redirectToRoute('User.ListeUsers', [
            'users' => $users,
            'user' => $user
        ]);
    }

    /**
     * @Route("/listeUsers/{id}/debannir", name="Admin.DebanUser")
     */
    public function debanUserAdmin(UserRepository $userRepository, EntityManagerInterface $doctrine, $id): Response
    {
        $users = $doctrine->getRepository(User::class)->findAll();
        $banned = $doctrine->getRepository(Banned::class)->find(1);
        $user = $doctrine->getRepository(User::class)->findOneBy(['id' => [$id]]);
        $user->setEstBanni(0);
        $banned->setNbBannis($banned->getNbBannis() - 1);
        $this->userManager->updateUser($user);
        $this->addFlash('success', 'Utilisateur débanni !');
        return $this->redirectToRoute('User.ListeUsers', [
            'users' => $users,
            'user' => $user
        ]);
    }

    /**
     * @Route("/users/{id}/suivre", name="User.Suivre")
     */
    public function suivreUser(UserRepository $userRepository, EntityManagerInterface $doctrine, $id): Response
    {
        $user = $doctrine->getRepository(User::class)->findOneBy(['id' => [$id]]);
        $userConnecte = $doctrine->getRepository(User::class)->find($this->getUser()->getId());
        $publicationsUser = $doctrine->getRepository(Publication::class)->findBy(['user' => $user]);

        foreach($publicationsUser as $publi){
            $newPubli = new Publication();
            $newPubli->setDate(new \DateTime());
            $newPubli->setUser($this->getUser());
            $newPubli->setChampPhoto($publi->getChampPhoto());
            $newPubli->setCommentaire($publi->getCommentaire());
            $newPubli->setUserQuiCommente($publi->getUserQuiCommente());
            $newPubli->setReponses($publi->getReponses());
            $newPubli->setPartage(1);
            $newPubli->setNbLike($publi->getNbLike());
            $newPubli->setNbDislike($publi->getNbDislike());
            $newPubli->setUserOriginal($publi->getUser()->getPseudo());
            $newPubli->setIdUserOriginal($publi->getUser()->getId());
            $newPubli->setPubliSuivie(1);
            $newPubli->setUserAyantSuivi($this->getUser()->getId());
            $newPubli->setSpotify($publi->getSpotify());
            $newPubli->setDeezer($publi->getDeezer());
            $newPubli->setListeUserQuiLike($publi->getListeUserQuiLike());
            $newPubli->setListeUserQuiDislike($publi->getListeUserQuiDislike());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($newPubli);
            $entityManager->flush();
        }

        $arrayUsersSuivis = $userConnecte->getUserSuivis();
        if($arrayUsersSuivis == null){
            $userConnecte->setUserSuivis(array($publi->getUser()->getId()));
        }
        else{
            array_push($arrayUsersSuivis, $publi->getUser()->getId());
            $userConnecte->setUserSuivis($arrayUsersSuivis);
        }

        return $this->redirectToRoute('User.accueil');
    }

    /**
     * @Route("/users/{id}/desabonner", name="User.Desabonner")
     */
    public function desabonner(UserRepository $userRepository, EntityManagerInterface $doctrine, $id): Response
    {
        $user = $doctrine->getRepository(User::class)->findOneBy(['id' => [$id]]);
        $userConnecte = $doctrine->getRepository(User::class)->find($this->getUser()->getId());
        $publicationsUser = $doctrine->getRepository(Publication::class)->findBy(['user' => $user, 'publiSuivie' => 1]);


        foreach($publicationsUser as $publi){
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($publi);
            $entityManager->flush();
        }



        $arrayUsersSuivis = $userConnecte->getUserSuivis();
        if($user->getId() == $arrayUsersSuivis) {
            var_dump("dedans");
        }

        return $this->redirectToRoute('User.accueil');
    }

    /**
     * @Route("/FAQ", name="FAQ.Afficher")
     */
    public function faq(UserRepository $userRepository, EntityManagerInterface $doctrine): Response
    {
        return $this->render('faq/showFaq.html.twig');
    }
}