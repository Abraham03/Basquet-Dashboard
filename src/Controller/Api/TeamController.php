<?php
class TeamController extends BaseController
{
    private $repo;

    public function __construct()
    {
        $this->repo = new TeamRepository(Database::getInstance());
    }

    // --- NUEVO: Helper para borrar físicamente el logo del servidor ---
    private function deleteOldLogo(?string $logoUrl) {
        if (!empty($logoUrl)) {
            // Limpiamos la ruta relativa (../assets/...) para obtener la absoluta
            $cleanPath = str_replace(['../', './'], '', $logoUrl);
            $absolutePath = realpath(__DIR__ . '/../../../' . $cleanPath);
            
            // Si el archivo existe en el disco duro, lo eliminamos
            if ($absolutePath && file_exists($absolutePath) && is_file($absolutePath)) {
                unlink($absolutePath);
            }
        }
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

        $tournamentId = isset($cleanData['tournament_id']) ? (int)$cleanData['tournament_id'] : 0;

        // --- NUEVA VALIDACIÓN: ¿Existe ya un equipo con este nombre EN ESTE TORNEO? ---
        if ($tournamentId > 0 && $this->repo->teamNameExistsInTournament($upperName, $tournamentId)) {
            Response::error("Ya existe un equipo registrado con el nombre '$upperName' en este torneo.", 400);
            return;
        }

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
            if ($tournamentId > 0) {
                $this->repo->attachTeamToTournament((int) $newId, $tournamentId);
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

        $tournamentId = isset($cleanData['tournament_id']) ? (int)$cleanData['tournament_id'] : 0;

        // --- NUEVA VALIDACIÓN: ¿Existe el nombre en el torneo? (Ignorando a sí mismo) ---
        if ($tournamentId > 0 && $this->repo->teamNameExistsInTournament($upperName, $tournamentId, (int)$cleanData['id'])) {
            Response::error("Ya existe otro equipo en este torneo utilizando el nombre '$upperName'.", 400);
            return;
        }

        try {
            // 1. Obtener el logo actual ANTES de hacer cualquier cambio
            $oldLogoUrl = $this->repo->getLogoUrl((int)$cleanData['id']);

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

            // 2. Si se subió un NUEVO logo exitosamente, borramos el VIEJO del disco duro
            if ($logoUrl !== null && $oldLogoUrl !== null) {
                $this->deleteOldLogo($oldLogoUrl);
            }

            // AQUÍ LA LÓGICA SOLICITADA:
            // Si viene con un ID de torneo válido (> 0), vinculamos el equipo a ese torneo.
            if ($tournamentId > 0) {
                $this->repo->updateTeamTournament((int) $cleanData['id'], $tournamentId);
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
            // 1. Obtener el logo antes de borrar el registro
            $oldLogoUrl = $this->repo->getLogoUrl((int) $id);

            // 2. Borrar de la base de datos (Esto puede fallar si el equipo tiene partidos jugados)
            $this->repo->delete((int) $id);

            // 3. Si se borró de la BD con éxito, eliminamos el archivo físico
            if ($oldLogoUrl !== null) {
                $this->deleteOldLogo($oldLogoUrl);
            }

            Response::success('Equipo eliminado');
        } catch (Exception $e) {
            Response::error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function downloadReports($data) {
        $this->validate($data, [
            'team_id' => 'required|integer',
            'tournament_id' => 'required|integer'
        ]);

        $teamId = (int)$data['team_id'];
        $tournamentId = (int)$data['tournament_id'];

        try {
            $matches = $this->repo->getTeamMatchesWithReports($teamId, $tournamentId);
            
            if (empty($matches)) {
                Response::error("Este equipo aún no tiene actas registradas en este torneo.", 404);
            }

            // Usamos ZipArchive nativo de PHP
            $zip = new ZipArchive();
            $zipFileName = "actas_equipo_{$teamId}_torneo_{$tournamentId}_" . time() . ".zip";
            
            // Creamos carpeta de descargas temporales si no existe
            $downloadsDir = __DIR__ . '/../../../assets/downloads/';
            if (!is_dir($downloadsDir)) {
                mkdir($downloadsDir, 0755, true);
            }
            
            $zipPath = $downloadsDir . $zipFileName;

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new Exception("Error interno: No se pudo crear el archivo ZIP.");
            }

            $count = 1;
            foreach ($matches as $match) {
                // Ajustamos la ruta relativa de la BD a una ruta absoluta en el servidor
                $cleanRelativePath = ltrim(str_replace('../', '', $match['pdf_url']), '/');
                $absoluteFilePath = __DIR__ . '/../../../' . $cleanRelativePath;

                if (file_exists($absoluteFilePath)) {
                    // Nombrar los archivos en orden cronológico dentro del ZIP
                    $dateStr = date('Ymd', strtotime($match['match_date']));
                    $safeTeamA = preg_replace('/[^a-zA-Z0-9]/', '_', $match['team_a_name']);
                    $safeTeamB = preg_replace('/[^a-zA-Z0-9]/', '_', $match['team_b_name']);
                    
                    // Formato: 01_20260222_Lakers_vs_Bulls.pdf
                    $localName = sprintf("%02d_%s_%s_vs_%s.pdf", $count, $dateStr, $safeTeamA, $safeTeamB);
                    
                    $zip->addFile($absoluteFilePath, $localName);
                    $count++;
                }
            }

            $zip->close();

            if ($count === 1) { // No se añadió ningún archivo físico
                @unlink($zipPath);
                Response::error("Las actas existen en el registro, pero los archivos PDF físicos no se encontraron en el servidor.", 404);
            }

            // Retornamos la URL pública para que JavaScript inicie la descarga
            $downloadUrl = '../assets/downloads/' . $zipFileName;
            Response::success('Archivo ZIP generado correctamente', ['download_url' => $downloadUrl]);

        } catch (Exception $e) {
            Logger::write("Error en downloadReports: " . $e->getMessage());
            Response::error($e->getMessage(), 500);
        }
    }
}
?>