# Configuración de Supabase

1. Crea un proyecto en Supabase y configura la **Project URL** y la clave **publishable** (en proyectos anteriores aparece como `anon`) en `supabase-config.js`.
2. En un proyecto vacío, ejecuta una vez todo el contenido actualizado de `schema.sql`. Si ya instalaste una versión anterior, ejecuta `user_stories_upgrade.sql` en su lugar. No ejecutes ambos archivos sobre la misma base.
3. Inicia la web, crea tu cuenta y confirma el correo si Supabase lo solicita. Si registraste la cuenta antes de instalar el esquema, continúa con el siguiente paso para crear el perfil que faltó.
4. Ejecuta este bloque en SQL Editor para asegurar que tu perfil exista y asignarte el rol administrador. Reemplaza `TU_CORREO` por el correo de tu cuenta:

   ```sql
   insert into public.profiles (id, full_name, email, privacy_consent_at, privacy_policy_version)
   select id, coalesce(nullif(trim(raw_user_meta_data->>'full_name'), ''), 'Estudiante'), email,
          now(), 'administrative-bootstrap'
   from auth.users where email = 'TU_CORREO'
   on conflict (id) do nothing;

   update public.profiles set role = 'admin' where email = 'TU_CORREO';
   ```

5. Obtén el UUID con `select id from public.profiles where email = 'TU_CORREO';`. En `seed.sql`, reemplaza `ADMIN_USER_ID` por ese UUID y ejecuta el archivo. Carga los módulos y ejercicios iniciales.
6. Recarga la app e inicia sesión.

El esquema asigna el rol `student` al registrarse. Los roles `teacher`, `content_manager` y `admin` se asignan de forma administrativa. No pongas la clave `service_role` en `supabase-config.js`.

RLS restringe perfiles, cursos, contenidos e intentos por rol. Un estudiante solo puede consultar y evaluar módulos vinculados a un curso donde está matriculado. `start_attempt` crea el intento y `submit_attempt` calcula el puntaje en PostgreSQL. La práctica usa `check_practice_answer`, que entrega retroalimentación y guarda el avance; las claves de evaluación no llegan al navegador. El gestor puede consultar la respuesta correcta únicamente mediante una función que valida su rol.
