<?php
class UserController extends BaseController {
    private $repo;

    public function __construct() {
        $this->repo = new UserRepository(Database::getInstance());
    }

    public function getAll() {
        try {
            Response::success('Lista de usuarios', $this->repo->getAll());
        } catch (Exception $e) {
            Response::error('Error al obtener usuarios', 500);
        }
    }

    public function create($data) {
        $clean = $this->sanitize($data);
        $this->validate($clean, [
            'username' => 'required',
            'password' => 'required',
            'role'     => 'required|in:superadmin,coach'
        ]);

        $teamId = ($clean['role'] === 'coach' && !empty($clean['team_id'])) ? (int)$clean['team_id'] : null;
        
        // Hashear contraseña profesionalmente
        $hash = password_hash($clean['password'], PASSWORD_DEFAULT);

        try {
            $this->repo->create($clean['username'], $hash, $clean['role'], $teamId);
            Response::success('Usuario creado correctamente');
        } catch (Exception $e) {
            Response::error('El nombre de usuario ya existe o hubo un error', 500);
        }
    }

    public function update($data) {
        $clean = $this->sanitize($data);
        $this->validate($clean, [
            'id'       => 'required|integer',
            'username' => 'required',
            'role'     => 'required|in:superadmin,coach'
        ]);

        $teamId = ($clean['role'] === 'coach' && !empty($clean['team_id'])) ? (int)$clean['team_id'] : null;
        $hash = !empty($clean['password']) ? password_hash($clean['password'], PASSWORD_DEFAULT) : null;

        try {
            $this->repo->update((int)$clean['id'], $clean['username'], $hash, $clean['role'], $teamId);
            Response::success('Usuario actualizado correctamente');
        } catch (Exception $e) {
            Response::error('Error al actualizar el usuario', 500);
        }
    }

    public function delete($id) {
        $this->validate(['id' => $id], ['id' => 'required|integer']);
        try {
            $this->repo->delete((int)$id);
            Response::success('Usuario eliminado permanentemente');
        } catch (Exception $e) {
            Response::error('Error al eliminar usuario', 500);
        }
    }
}
?>