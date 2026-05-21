<?php


session_start();
require_once 'includes/db.php';

$erro = '';

if (isset($_POST["btnIniciarSessao"])) {
  $login = trim($_POST["login"] ?? '');
  $password = trim($_POST["password"] ?? '');

  $stmt = $pdo->prepare("
        SELECT 
            u.id_utilizador,
            u.username,
            u.password_hash,
            u.ativo,
            p.nome,
            p.funcao
        FROM UTILIZADOR u
        JOIN PROFISSIONAL p ON p.id_profissional = u.id_profissional
        WHERE u.username = :login
          AND u.ativo = 1
        LIMIT 1
    ");

  $stmt->execute([
    ':login' => $login
  ]);

  $linha = $stmt->fetch();

  if (!$linha || !password_verify($password, $linha["password_hash"])) {
    $erro = "Utilizador e/ou palavra-passe inválidos.";
  } else {
    session_regenerate_id(true);

    $_SESSION['id_utilizador'] = $linha["id_utilizador"];
    $_SESSION['currentNome'] = $linha["nome"];
    $_SESSION['currentLogin'] = $linha["username"];
    $_SESSION['currentFuncao'] = $linha["funcao"];

    header('Location: dashboard.php');
    exit;
  }
}

?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sessão | PsiqSys</title>

  <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="vendors/base/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="shortcut icon" href="images/favicon2.png" type="image/x-icon">
</head>

<body class="login-page">

  <main class="login-wrapper">
    <section class="login-card">

      <div class="login-brand">
        <img src="images/logo.png" alt="PsiqSys">
      </div>

      <div class=" login-header">
        <h2>Bem-vindo</h2>
        <p>Inicie sessão para aceder ao backoffice.</p>
      </div>

      <?php if (!empty($erro)): ?>
        <div class="alert alert-danger">
          <i class="ti ti-alert-circle"></i>
          <span><?= htmlspecialchars($erro) ?></span>
        </div>
      <?php endif; ?>

      <form method="post" action="index.php" class="login-form">

        <div class="form-group">
          <label class="form-label" for="login">Utilizador</label>

          <div class="input-icon">
            <i class="ti ti-user"></i>
            <input
              type="text"
              name="login"
              id="login"
              class="form-control"
              placeholder="Introduza o utilizador"
              required
              autofocus>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Palavra-passe</label>

          <div class="input-icon">
            <i class="ti ti-lock"></i>
            <input
              type="password"
              name="password"
              id="password"
              class="form-control"
              placeholder="Introduza a palavra-passe"
              required>
          </div>
        </div>

        <button
          type="submit"
          name="btnIniciarSessao"
          class="btn btn-primary login-btn">
          <i class="ti ti-login"></i>
          Iniciar sessão
        </button>

      </form>

      <div class="login-footer">
        <p>Não tem uma conta?</p>
        <span>Contacte o administrador do sistema.</span>
      </div>

    </section>
  </main>

</body>

</html>