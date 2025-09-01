Sistema de Escalas para Bombeiros

1.	Introdução
O projeto de Escala para Bombeiros visa resolver a dificuldade de gestão manual de plantões e turnos, tornando o processo mais ágil,
confiável e acessível por meio de um sistema web integrado a banco de dados e calendário online.

3.	Objetivos
Objetivo Geral: Desenvolver um sistema online para gestão de escalas de bombeiros, com visualização simples e acessível.

Objetivos Específicos:
-	Criar banco de dados relacional para armazenar bombeiros, tipos de plantão, horas extras e substituições.
-	Desenvolver interface web responsiva (HTML, CSS e JS) hospedada em servidor gratuito (InfinityFree).
-	Integrar com Google Apps Script e FullCalendar para exibição dinâmica da escala.
-	Permitir acesso seguro por autenticação.

3.	Justificativa
O sistema busca otimizar o controle de escalas, reduzir erros e facilitar o acesso para cerca de 20 bombeiros,
sem necessidade de softwares pagos e com possibilidade de expansão futura.

5.	Fundamentação Teórica
Foram utilizados conceitos de sistemas distribuídos e banco de dados relacional, apoiados nas seguintes ferramentas:
-	InfinityFree (hospedagem gratuita com SSL).
-	MySQL / phpMyAdmin (armazenamento e gerenciamento de dados).
-	Google Apps Script (integrações e API).
-	FullCalendar (biblioteca JavaScript para calendário interativo).

5.	Metodologia
A metodologia adotada incluiu:
-	Levantamento de requisitos junto aos bombeiros.
-	Modelagem do banco de dados.
-	Desenvolvimento web em PHP, HTML, CSS e JS.
-	Integração com Google Calendar.
-	Testes em dispositivos móveis.

6.	Arquitetura do Sistema
O sistema é estruturado em três camadas:
-	Apresentação: interface web responsiva com FullCalendar.
-	Negócio: regras de escala, substituição e autenticação.
-	Dados: MySQL no InfinityFree.

7.	Resultados Esperados
-	Sistema funcional de escalas em tempo real.
-	Redução de erros na marcação de plantões.
-	Acesso rápido e confiável por dispositivos móveis.

8.	Considerações Finais
O sistema é escalável, podendo futuramente evoluir para um aplicativo móvel, além de possibilitar notificações automáticas via e-mail ou WhatsApp.

9.	Referências
-	FullCalendar: https://fullcalendar.io
-	InfinityFree Hosting: https://infinityfree.net
-	W3Schools (PHP, MySQL, HTML, CSS, JS)
-	Google Apps Script: https://developers.google.com/apps-script
