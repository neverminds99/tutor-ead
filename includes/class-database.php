<?php

/**

 * Tutor EAD – Estrutura de Banco de Dados

 *

 *  ▸ Classe: TutorEAD\Database

 *  ▸ Responsável por criar e atualizar **todas** as tabelas necessárias

 *    ao funcionamento do plugin.

 *

 *  Tabelas mantidas por esta classe

 *  ────────────────────────────────────────────────────────────────────

 *   1. tutoread_courses                    – Cursos

 *   2. tutoread_alertas                    – Alertas / Avisos

 *   3. tutoread_alertas_views              – Contador de visualizações de alertas

 *   4. tutoread_modules                    – Módulos

 *   5. tutoread_lessons                    – Aulas

 *   6. matriculas                          – Matrículas de usuários em cursos

 *   7. atividades                          – Banco de atividades (provas, quizzes, etc.)

 *   8. tutoread_course_activities_presenciais – Liga atividade ↔ curso (presencial)

 *   9. boletins                            – Notas/feedbacks

 *  10. progresso_aulas                     – Status de conclusão das aulas

 *  11. tutoread_comments                   – Comentários em aulas

 *  12. perguntas                           – Perguntas das atividades

 *  13. alternativas                        – Alternativas das perguntas

 *  14. respostas                           – Respostas dos alunos

 *  15. tutoread_course_activities          – Liga atividade ↔ curso/módulo/aula (EAD)

 *  16. temp_login_tokens                   – Tokens de login temporário

 *

 *  Fluxo:

 *   • `dbDelta()` cria ou altera as tabelas segundo as definições abaixo.

 *   • Após o loop, correções pontuais são aplicadas via `ensure_column()`.

 *

 *  >>> Atualize a constante VERSION sempre que modificar a estrutura! <<<

 */



namespace TutorEAD;



defined( 'ABSPATH' ) || exit;



class Database {



	/** Versão atual do esquema de banco de dados. */

	const VERSION = '1.3.5';



	/**

	 * Cria / atualiza todas as tabelas do Tutor EAD.

	 */

	public static function create_tables() {

		global $wpdb;



		$charset_collate = $wpdb->get_charset_collate();



		/*------------------------------------------------------------------

		 * 1) DEFINIÇÕES SQL COMPLETAS

		 *-----------------------------------------------------------------*/

		$tables = [

			// 1. Cursos
			"CREATE TABLE {$wpdb->prefix}tutoread_courses (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				title VARCHAR(255) NOT NULL,
				description TEXT,
				professor_id BIGINT(20) UNSIGNED,
				max_students INT(11),
				capa_img VARCHAR(255) NULL,
				capa_img_crop VARCHAR(255) NULL,
				PRIMARY KEY  (id)
			) $charset_collate;",

			// 2. Alertas / Avisos
			"CREATE TABLE {$wpdb->prefix}tutoread_alertas (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id TEXT NULL,
				course_id TEXT NULL,
				mensagem TEXT NOT NULL,
				tipo ENUM('alerta','aviso') NOT NULL,
				status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
				limite_exibicoes INT UNSIGNED NULL,
				local_exibicao ENUM('dashboard','aula') NOT NULL DEFAULT 'dashboard',
				data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id)
			) $charset_collate;",

			// 3. Visualizações de alertas
			"CREATE TABLE {$wpdb->prefix}tutoread_alertas_views (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				alert_id BIGINT UNSIGNED NOT NULL,
				user_id BIGINT UNSIGNED NOT NULL,
				views INT UNSIGNED NOT NULL DEFAULT 0,
				last_view DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY alert_user (alert_id,user_id),
				KEY alert_id (alert_id),
				KEY user_id (user_id)
			) $charset_collate;",

			// 4. Módulos
			"CREATE TABLE {$wpdb->prefix}tutoread_modules (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				course_id BIGINT(20) UNSIGNED NOT NULL,
				parent_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
				title VARCHAR(255) NOT NULL,
				description TEXT,
				module_order INT NOT NULL DEFAULT 0,
				capa_img VARCHAR(255) NULL,
				PRIMARY KEY  (id),
				KEY course_id (course_id)
			) $charset_collate;",

			// 5. Aulas
			"CREATE TABLE {$wpdb->prefix}tutoread_lessons (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				module_id BIGINT(20) UNSIGNED NOT NULL,
				title VARCHAR(255) NOT NULL,
				description TEXT,
				content TEXT,
				video_url VARCHAR(255) NULL,
				lesson_format VARCHAR(20) NOT NULL DEFAULT 'legacy',
				lesson_order INT NOT NULL DEFAULT 0,
				capa_img VARCHAR(255) NULL,
				PRIMARY KEY  (id),
				KEY module_id (module_id)
			) $charset_collate;",

			// 5.1 Aula Blocos
			"CREATE TABLE {$wpdb->prefix}tutoread_lesson_blocks (
				block_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				lesson_id BIGINT(20) UNSIGNED NOT NULL,
				block_type VARCHAR(50) NOT NULL,
				block_content LONGTEXT NULL,
				block_order INT NOT NULL DEFAULT 0,
				PRIMARY KEY  (block_id),
				KEY lesson_id (lesson_id)
			) $charset_collate;",

			// 6. Matrículas
			"CREATE TABLE {$wpdb->prefix}matriculas (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT UNSIGNED NOT NULL,
				course_id BIGINT UNSIGNED NOT NULL,
				role ENUM('aluno','professor') NOT NULL,
				data_matricula DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
				PRIMARY KEY  (id),
				KEY user_id (user_id),
				KEY course_id (course_id)
			) $charset_collate;",

			// 7. Atividades
			"CREATE TABLE {$wpdb->prefix}atividades (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				titulo VARCHAR(255) NOT NULL,
				descricao TEXT NULL,
				nota_maxima FLOAT NOT NULL,
				num_tentativas INT NOT NULL,
				usar_valores_individuais TINYINT(1) DEFAULT 0,
				data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
				is_externa TINYINT(1) DEFAULT 0,
				link_externo VARCHAR(255) NULL,
				dias_visualizacao INT NOT NULL DEFAULT 0,
				PRIMARY KEY  (id)
			) $charset_collate;",

			// 8. Associação atividade presencial
			"CREATE TABLE {$wpdb->prefix}tutoread_course_activities_presenciais (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				activity_id BIGINT UNSIGNED NOT NULL,
				course_id BIGINT UNSIGNED NOT NULL,
				data_associacao DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
				PRIMARY KEY  (id),
				KEY activity_id (activity_id),
				KEY course_id (course_id)
			) $charset_collate;",

			// 9. Boletins
			"CREATE TABLE {$wpdb->prefix}boletins (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				aluno_id BIGINT UNSIGNED NULL,
				course_id BIGINT UNSIGNED NULL,
				course_title VARCHAR(255) NOT NULL,
				atividade_id BIGINT UNSIGNED NULL,
				atividade_title VARCHAR(255) NOT NULL,
				nota FLOAT NOT NULL,
				feedback TEXT NULL,
				data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
				PRIMARY KEY  (id),
				KEY aluno_id (aluno_id),
				KEY course_id (course_id),
				KEY atividade_id (atividade_id)
			) $charset_collate;",

			// 10. Progresso das aulas
			"CREATE TABLE {$wpdb->prefix}progresso_aulas (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				aluno_id BIGINT UNSIGNED NOT NULL,
				aula_id BIGINT UNSIGNED NOT NULL,
				status ENUM('concluido','em_andamento','nao_iniciado') DEFAULT 'nao_iniciado',
				data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
				PRIMARY KEY  (id),
				KEY aluno_id (aluno_id),
				KEY aula_id (aula_id)
			) $charset_collate;",

			// 11. Comentários
			"CREATE TABLE {$wpdb->prefix}tutoread_comments (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				lesson_id BIGINT(20) UNSIGNED NOT NULL,
				user_id BIGINT(20) UNSIGNED NOT NULL,
				comment TEXT NOT NULL,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
				PRIMARY KEY  (id),
				KEY lesson_id (lesson_id),
				KEY user_id (user_id)
			) $charset_collate;",

			// 12. Perguntas
			"CREATE TABLE {$wpdb->prefix}perguntas (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				atividade_id BIGINT UNSIGNED NOT NULL,
				titulo VARCHAR(255) NOT NULL,
				enunciado TEXT NOT NULL,
				valor FLOAT NULL,
				PRIMARY KEY  (id),
				KEY atividade_id (atividade_id)
			) $charset_collate;",

			// 13. Alternativas
			"CREATE TABLE {$wpdb->prefix}alternativas (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				pergunta_id BIGINT UNSIGNED NOT NULL,
				texto TEXT NOT NULL,
				correta TINYINT(1) DEFAULT 0,
				PRIMARY KEY  (id),
				KEY pergunta_id (pergunta_id)
			) $charset_collate;",

			// 14. Respostas
			"CREATE TABLE {$wpdb->prefix}respostas (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				atividade_id BIGINT UNSIGNED NOT NULL,
				aluno_id BIGINT UNSIGNED NOT NULL,
				tentativa INT NOT NULL,
				respostas TEXT NOT NULL,
				nota_obtida FLOAT NOT NULL,
				data_resposta DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
				PRIMARY KEY  (id),
				KEY atividade_id (atividade_id),
				KEY aluno_id (aluno_id)
			) $charset_collate;",

			// 15. Liga atividade EAD
			"CREATE TABLE {$wpdb->prefix}tutoread_course_activities (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				activity_id BIGINT UNSIGNED NOT NULL,
				course_id BIGINT UNSIGNED NOT NULL,
				module_id BIGINT UNSIGNED NULL,
				lesson_id BIGINT UNSIGNED NULL,
				position ENUM('antes','depois') NOT NULL DEFAULT 'depois',
				PRIMARY KEY  (id),
				KEY activity_id (activity_id),
				KEY course_id (course_id)
			) $charset_collate;",

			// 16. Tokens de login temporário
			"CREATE TABLE {$wpdb->prefix}temp_login_tokens (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT(20) UNSIGNED NOT NULL,
				token VARCHAR(100) NOT NULL,
				expiration DATETIME NOT NULL,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
				status ENUM('active','inactive') NOT NULL DEFAULT 'active',
				login_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY user_id (user_id)
			) $charset_collate;",

			// 17. Informações de Usuários
			"CREATE TABLE {$wpdb->prefix}tutoread_user_info (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT(20) UNSIGNED NOT NULL,
				full_name VARCHAR(255) NULL,
				profile_photo_url VARCHAR(255) NULL,
				phone_number VARCHAR(25) NULL,
                cpf VARCHAR(20) NULL,
                rg VARCHAR(20) NULL,
                endereco VARCHAR(255) NULL,
                cep VARCHAR(10) NULL,
                cidade VARCHAR(100) NULL,
                estado VARCHAR(100) NULL,
                valor_contrato VARCHAR(50) NULL,
				bio TEXT NULL,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY user_id (user_id)
			) $charset_collate;",

			// 18. Meta de Informações de Usuários
            "CREATE TABLE {$wpdb->prefix}tutoread_user_info_meta (
                meta_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_info_id BIGINT(20) UNSIGNED NOT NULL,
                meta_key VARCHAR(255) NULL,
                meta_value LONGTEXT NULL,
                PRIMARY KEY  (meta_id),
                KEY user_info_id (user_info_id),
                KEY meta_key (meta_key(191))
            ) $charset_collate;",

            // 19. Log de Atividades do Aluno
            "CREATE TABLE {$wpdb->prefix}tutoread_student_activity_log (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) UNSIGNED NOT NULL,
                activity_type varchar(255) NOT NULL,
                course_id bigint(20) UNSIGNED DEFAULT NULL,
                lesson_id bigint(20) UNSIGNED DEFAULT NULL,
                access_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                details text DEFAULT NULL,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY course_id (course_id),
                KEY lesson_id (lesson_id)
            ) $charset_collate;",

            // 20. Advertisements
            "CREATE TABLE {$wpdb->prefix}tutoread_advertisements (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                image_url VARCHAR(255) NOT NULL,
                link_url VARCHAR(255) NOT NULL,
                target_location VARCHAR(100) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                display_chance INT(3) NOT NULL DEFAULT 100,
                date_created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id),
                KEY target_location (target_location)
            ) $charset_collate;",

            // 21. Advertisement Stats
            "CREATE TABLE {$wpdb->prefix}tutoread_advertisement_stats (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                ad_id BIGINT(20) UNSIGNED NOT NULL,
                views BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                clicks BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (id),
                UNIQUE KEY ad_id (ad_id)
            ) $charset_collate;",
        ];



		/*------------------------------------------------------------------

		 * 2) EXECUÇÃO via dbDelta()

		 *-----------------------------------------------------------------*/

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( $tables as $sql ) {

			dbDelta( $sql );

		}



		/*------------------------------------------------------------------

		 * 3) CORREÇÕES PONTUAIS (failsafe)

		 *-----------------------------------------------------------------*/

				self::ensure_column(

					"{$wpdb->prefix}tutoread_alertas",

					'limite_exibicoes',

					"ALTER TABLE {$wpdb->prefix}tutoread_alertas

					 ADD limite_exibicoes INT UNSIGNED NULL AFTER status"

				);

		

				// Garante que a coluna module_id em tutoread_course_activities pode ser nula

				self::ensure_column_is_nullable(

					"{$wpdb->prefix}tutoread_course_activities",

					'module_id'

				);

			}

		

			/**

			 * Garante que uma coluna específica possa aceitar valores nulos.

			 *

			 * @param string $table      Tabela

			 * @param string $column     Coluna que deve ser nula

			 */

			private static function ensure_column_is_nullable( $table, $column ) {

				global $wpdb;

		

				$is_nullable = $wpdb->get_var(

					$wpdb->prepare(

						"SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",

						DB_NAME,

						$table,

						$column

					)

				);

		

				// Se a coluna existe e está definida como 'NO' (NOT NULL), altera-a.

				if ( $is_nullable === 'NO' ) {

					$wpdb->query(

						"ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` BIGINT(20) UNSIGNED NULL DEFAULT NULL"

					);

				}

			}

		

			/**

			 * Garante a existência de uma coluna específica (proteção extra).

			 *

			 * @param string $table      Tabela

			 * @param string $column     Coluna que deve existir

			 * @param string $alter_sql  SQL de criação caso não exista

			 */

			private static function ensure_column( $table, $column, $alter_sql ) {

		global $wpdb;



		$exists = $wpdb->get_var(

			$wpdb->prepare(

				"SHOW COLUMNS FROM {$table} LIKE %s",

				$column

			)

		);



		if ( ! $exists ) {

			$wpdb->query( $alter_sql );

		}

	}

}

