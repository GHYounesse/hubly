<?php

namespace App\Controller\Technology;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Technologie;
use Symfony\Component\HttpFoundation\Request;
use App\Form\SearchType;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\TechnologieType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\TechnologieRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Entity\Projet;
/**
 * Class TechnologieController
 * @package App\Controller\Technology
 * Controller managing CRUD operations for Technologie entities.
 *
 * Provides functionality to:
 *  - List all technologies with optional search
 *  - Create new technologies
 *  - Update existing technologies
 *  - Delete technologies
 *  - Show details of a single technology
 *
 * Uses services such as:
 *  - EntityManagerInterface / ManagerRegistry to manage entity persistence
 */
#[Route('/technologie')]
final class TechnologieController extends AbstractController
{   
    /**
     * List all technologies or search by name.
     *
     * @param Request $request Current HTTP request
     * @param TechnologieRepository $repository Repository to fetch Technologie entities
     *
     * @return Response Rendered list template
     */
    #[Route('/', name: 'app_technologie_index')]
    public function index(Request $request, TechnologieRepository $repository): Response
    {         
        // Création du formulaire de recherche
        $form = $this->createForm(SearchType::class);
        $form->handleRequest($request);
        
        $technologies = $repository->findAll();

        // Traitement de la recherche
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (!empty($data['search'])) {
                $technologies = $repository->createQueryBuilder('t')
                    ->where('LOWER(t.nom) LIKE LOWER(:search)')
                    ->setParameter('search', '%' . $data['search'] . '%')
                    ->getQuery()
                    ->getResult();
            }
        }
        return $this->render('technologie/index.html.twig', [
            'technologies' => $technologies,
            'form' => $form->createView(),
        ]);
    }
    /**
     * Create a new Technologie entity.
     *
     * - Uses ManagerRegistry to persist the new entity.
     * - Sets created and updated timestamps automatically.
     *
     * @param ManagerRegistry $doctrine Doctrine service to get entity manager
     * @param Request $request HTTP request containing form submission
     *
     * @return Response Rendered form template or redirect after successful creation
     */
    #[Route('/new', name: 'app_technologie_new' ,methods: ["GET","POST"])]
    public function new(ManagerRegistry $doctrine ,Request $request ): Response
    {
        $technologie=new Technologie();
        $form = $this->createForm(TechnologieType::class , $technologie) ;
        $form->remove('created');
        $form->remove('updated');

        $technologie->setCreated(new \DateTime());
        $technologie->setUpdated(new \DateTime());
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            

            $logoFile = $form->get('logoFile')->getData();
            if ($logoFile instanceof UploadedFile) {
                    $newFilename = uniqid() . '.' . $logoFile->guessExtension();
                    $logoFile->move(
                        $this->getParameter('logos_directory'),
                        $newFilename
                    );
                    $technologie->setLogo("uploads/logos/$newFilename");
            }


            $entityManager=$doctrine->getManager();
            $entityManager->persist($technologie);
            $entityManager->flush();
            $this->addFlash('success', 'Technologie créée avec succès.');

            return $this->redirectToRoute('app_technologie_index');
        }

        return $this->render('technologie/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    /**
     * Update an existing Technologie entity.
     *
     * - Uses EntityManagerInterface / ManagerRegistry to persist changes.
     * - Updates the "updated" timestamp automatically.
     *
     * @param ManagerRegistry $doctrine Doctrine service to get entity manager
     * @param Request $request HTTP request containing form submission
     * @param int $id ID of the Technologie to update
     * @param EntityManagerInterface $entityManager Entity manager used to fetch and flush entity
     *
     * @return Response Rendered form template or redirect after successful update
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If no Technologie found for the given ID
     */
    #[Route('/update/{id}', name: 'app_technologie_update',methods: ["GET","POST"])]
    public function update(
    ManagerRegistry $doctrine,
        Request $request ,
        int $id ,
        EntityManagerInterface $entityManager
        ): Response
    {
        $technologie=$entityManager->getRepository(Technologie::class)->find($id);

        if(!$technologie)
        {
            throw $this->createNotFoundException('Technologie not found'.$id);
        }

        $form = $this->createForm(TechnologieType::class , $technologie);
        $oldLogo = $technologie->getLogo();
        $form->remove('created');
        $form->remove('updated');
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            /** @var UploadedFile|null $logoFile */
            $logoFile = $form->get('logoFile')->getData();

            if ($logoFile) {
                try {
                    // Delete old logo if exists and is different from default
                    if ($oldLogo && file_exists($this->getParameter('kernel.project_dir') . '/public/' . $oldLogo)) {
                        @unlink($this->getParameter('kernel.project_dir') . '/public/' . $oldLogo);
                    }

                    $newFilename = uniqid() . '.' . $logoFile->guessExtension();
                    $logoFile->move($this->getParameter('logos_directory'), $newFilename);
                    $technologie->setLogo('uploads/logos/' . $newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement du logo.');
                    return $this->render('technologie/update.html.twig', [
                        'form' => $form->createView(),
                        'technologie' => $technologie,
                    ]);
                }
            } else {
                // Keep existing logo
                $technologie->setLogo($oldLogo);
            }


            $entityManager=$doctrine->getManager();
            $technologie->setUpdated(new \DateTime());

            $entityManager->flush();
            $this->addFlash('success', 'Technologie mise à jour avec succès.');
                    
            return $this->redirectToRoute('app_technologie_index');
        }

        return $this->render('technologie/update.html.twig', [
            'form' => $form->createView(),
            'technologie'=> $technologie,
        ]);
    }

    /**
     * Delete an existing Technologie entity.
     *
     * - Uses ManagerRegistry / EntityManagerInterface to remove entity.
     *
     * @param int $id ID of the Technologie to delete
     * @param ManagerRegistry $doctrine Doctrine service to get entity manager
     *
     * @return Response Redirect to the technologies index page after deletion
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If no Technologie found for the given ID
     */
    #[Route('/delete/{id}', name: 'app_technologie_delete', methods: ['GET', 'POST'])]
    public function delete(int $id, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $technologie = $entityManager->getRepository(Technologie::class)->find($id);
    
        if (!$technologie) {
            throw $this->createNotFoundException('Aucun technologie trouvé avec l\'id ' . $id);
        }
        // Supprimer le projet après avoir supprimé les dépendances
        $entityManager->remove($technologie);
        $entityManager->flush();
    
        return $this->redirectToRoute('app_technologie_index');
    } 
    /**
     * Display a single Technologie entity.
     *
     * - Uses ManagerRegistry / EntityManagerInterface to fetch the entity by ID.
     *
     * @param ManagerRegistry $doctrine Doctrine service to get entity manager
     * @param int $id ID of the Technologie to show
     *
     * @return Response Rendered template with the Technologie details
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If no Technologie found for the given ID
     */
    #[Route('/show/{id}', name: 'app_technologie_show',methods: ["GET","POST"])]
    public function show( ManagerRegistry $doctrine ,  int $id): Response
    {
        $entityManager = $doctrine->getManager();
        // Récupération du développeur par son ID
        $technologie = $entityManager->getRepository(Technologie::class)->find($id);
        if(!$technologie){
            throw $this->createNotFoundException('Technologie not found'.$id);
        }
        $projets = $technologie->getProjets();
        
        
        return $this->render('technologie/show.html.twig', [
            'technologie' => $technologie,
            'projets' => $projets,
        ]);
    }
    #[Route('/{id}/search', name: 'technologie_search', methods: ['POST'])]
    public function search(
        int $id,
        Request $request,
        ManagerRegistry $doctrine
    ): Response {
        $entityManager = $doctrine->getManager();
        $technologie = $entityManager->getRepository(Technologie::class)->find($id);
        if (!$technologie) {
            
            return new Response('', 404);
        }

        $data = json_decode($request->getContent(), true);
        $search = $data['search'] ?? null;

        if ($search) {
            /** @var \App\Repository\ProjetRepository $projetRepo */
            $projetRepo = $doctrine->getRepository(Projet::class);
            $projets = $projetRepo->findByTechnologieAndSearch($search, $technologie);
        } else {
            $projets = $technologie->getProjets();
        }

        return $this->render('technologie/_projects_list.html.twig', [
            'projets' => $projets
        ]);
    }

}
