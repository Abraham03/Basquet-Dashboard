<?php
class TeamController extends BaseController
{
    private $repo;

    public function __construct()
    {
        $this->repo = new TeamRepository(Database::getInstance());
    }

    public function create($data)
    {
        $cleanData = $this->sanitize($data);

        // Validación estricta
        $this->validate($cleanData, [
            'name' => 'required',
            'tournament_id' => 'integer' // Opcional, pero si viene debe ser entero
        ]);

        // Convertir a mayúsculas
        $upperName = mb_strtoupper($cleanData['name'], 'UTF-8');
        $upperShortName = isset($cleanData['shortName']) ? mb_strtoupper($cleanData['shortName'], 'UTF-8') : '';

        try {
            // Usar nuestra nueva clase FileUploader
            $logoUrl = null;
            if (isset($_FILES['logo'])) {
                $dir = __DIR__ . '/../../../assets/team_logo/';
                $filename = FileUploader::uploadImage($_FILES['logo'], $dir, 'team_');
                if ($filename) {
                    $logoUrl = '../assets/team_logo/' . $filename;
                }
            }

            $newId = $this->repo->createTeam(
                $upperName,
                $upperShortName,
                $cleanData['coachName'] ?? '',
                $logoUrl
            );

            // Vincular al torneo si viene el ID
            if (!empty($cleanData['tournament_id'])) {
                $this->repo->attachTeamToTournament((int) $newId, (int) $cleanData['tournament_id']);
            }

            Response::success('Equipo creado con éxito', ['newId' => $newId], Response::HTTP_CREATED);

        } catch (Exception $e) {
            Logger::write("Error en create Team: " . $e->getMessage());
            Response::error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($data)
    {
        $cleanData = $this->sanitize($data);

        $this->validate($cleanData, [
            'id' => 'required|integer',
            'name' => 'required'
        ]);

        $upperName = mb_strtoupper($cleanData['name'], 'UTF-8');
        $upperShortName = isset($cleanData['shortName']) ? mb_strtoupper($cleanData['shortName'], 'UTF-8') : '';

        try {
            $logoUrl = null;
            if (isset($_FILES['logo'])) {
                $dir = __DIR__ . '/../../../assets/team_logo/';
                $filename = FileUploader::uploadImage($_FILES['logo'], $dir, 'team_');
                if ($filename) {
                    $logoUrl = '../assets/team_logo/' . $filename;
                }
            }

            // Actualizar datos básicos
            $this->repo->update(
                (int) $cleanData['id'],
                $upperName,
                $upperShortName,
                $cleanData['coachName'] ?? '',
                $logoUrl
            );

            // AQUÍ LA LÓGICA SOLICITADA:
            // Si viene con un ID de torneo válido (> 0), vinculamos el equipo a ese torneo.
            // Si viene con 0, lo ignora y solo actualiza la información general del equipo.
            if (isset($cleanData['tournament_id']) && (int)$cleanData['tournament_id'] > 0) {
                $this->repo->updateTeamTournament((int) $cleanData['id'], (int) $cleanData['tournament_id']);
            }

            Response::success('Equipo actualizado correctamente');

        } catch (Exception $e) {
            Logger::write("Error en update Team: " . $e->getMessage());
            Response::error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function detach($data)
    {
        $this->validate($data, [
            'id' => 'required|integer',
            'tournament_id' => 'required|integer'
        ]);

        try {
            $this->repo->detachTeamFromTournament((int) $data['id'], (int) $data['tournament_id']);
            Response::success('Equipo retirado del torneo');
        } catch (Exception $e) {
            Response::error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete($id)
    {
        $this->validate(['id' => $id], ['id' => 'required|integer']);

        try {
            $this->repo->delete((int) $id);
            Response::success('Equipo eliminado');
        } catch (Exception $e) {
            // AQUÍ ESTÁ EL PROBLEMA: Estás ignorando $e->getMessage()
            Response::error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
?>