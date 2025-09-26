
        <?php
        require_once 'MercadoPagoPix.php';
        header('Content-Type: application-json');

        $dados = [
            'value' => 1.0,
            'email' => 'marcosc974@gmail.com',
            'first_name' => '',
            'last_name' => '',
            'cpf' => ''
        ];
        
        $hp = new MercadoPagoPix();
        $hp->processPixDirect($dados);
        //$hp->getPaymentStatusDirect(127613873172);
        ?>
