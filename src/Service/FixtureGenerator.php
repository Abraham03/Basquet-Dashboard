<?php
// src/Service/FixtureGenerator.php

require_once __DIR__ . '/../Repository/TournamentRepository.php';

class FixtureGenerator {
    private $repository;

    public function __construct() {
        // --- CORRECCIÓN AQUÍ ---
        // El repositorio necesita la instancia de la base de datos.
        // Usamos el Singleton Database::getInstance()
        $this->repository = new TournamentRepository(Database::getInstance());
    }

    /**
     * Algoritmo principal: Método del Círculo (Round Robin)
     */
    public function generate($tournamentId, $config) {
        // Obtenemos la conexión para manejar la transacción aquí también
        $db = Database::getInstance()->getConnection();
        
        try {
            $db->begin_transaction();

            // 1. Limpiar fixture anterior si existe
            $this->repository->clearFixture($tournamentId);

            // 2. Guardar reglas
            $this->repository->saveRules($tournamentId, $config);

            // 3. Obtener equipos
            $teams = $this->repository->getTeamIds($tournamentId);
            $numTeams = count($teams);

            if ($numTeams < 2) {
                throw new Exception("Se necesitan al menos 2 equipos para generar un fixture.");
            }

            // Si es impar, agregamos un "BYE" (null)
            if ($numTeams % 2 != 0) {
                array_push($teams, null); // null representa descanso
                $numTeams++;
            }

            $totalRounds = $numTeams - 1; // Rondas por vuelta
            $matchesPerRound = $numTeams / 2;
            $laps = $config['matchups_per_pair'] ?? 1; // Vueltas (Ida, Vuelta, etc.)

            // 4. Generar Rondas y Partidos
            $roundCounter = 1;

            for ($lap = 0; $lap < $laps; $lap++) {
                // En vueltas pares (Ida), local es A. En impares (Vuelta), local es B
                $swapHomeAway = ($lap % 2 != 0); 

                for ($round = 0; $round < $totalRounds; $round++) {
                    // Crear la Jornada en BD
                    $roundName = "Jornada " . $roundCounter;
                    $roundId = $this->repository->createRound($tournamentId, $roundName, $roundCounter);
                    $roundCounter++;

                    // Generar pares del método del círculo
                    for ($match = 0; $match < $matchesPerRound; $match++) {
                        $home = $teams[$match];
                        $away = $teams[$numTeams - 1 - $match];

                        // Si uno es null (Bye), el otro descansa.
                        if ($home === null || $away === null) {
                            continue;
                        }

                        // --- MEJORA PROFESIONAL: BALANCEO DE LOCALÍA (Algoritmo de Berger) ---
                        // Si es el partido del equipo fijo ($match === 0) y la jornada es impar, 
                        // invertimos su localía para que no juegue siempre en casa.
                        if ($match === 0 && $round % 2 === 1) {
                            $temp = $home;
                            $home = $away;
                            $away = $temp;
                        }
                        // ----------------------------------------------------------------------

                        // Alternar localía general si es la segunda vuelta (partidos de revancha)
                        if ($swapHomeAway) {
                            $this->repository->createFixture($tournamentId, $roundId, $away, $home);
                        } else {
                            $this->repository->createFixture($tournamentId, $roundId, $home, $away);
                        }
                    }

                    // ROTACIÓN DE EQUIPOS (Algoritmo del Círculo)
                    // El primer elemento ($teams[0]) se queda fijo.
                    // El último elemento se mueve a la posición 1.
                    $lastTeam = array_pop($teams);
                    array_splice($teams, 1, 0, [$lastTeam]);
                }
            }

            $db->commit();
            return ['status' => 'success', 'message' => 'Fixture generado exitosamente con ' . ($roundCounter - 1) . ' jornadas.'];

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
}
?>