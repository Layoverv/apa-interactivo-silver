-- APA Interactivo: instalación limpia para Supabase/PostgreSQL.
-- La app usa la clave publishable; nunca expone service_role en el navegador.

create extension if not exists pgcrypto;

create type public.app_role as enum ('student', 'teacher', 'content_manager', 'admin');
create type public.attempt_status as enum ('in_progress', 'submitted');
create type public.question_activity as enum ('practice', 'evaluation', 'both');

create table public.profiles (
  id uuid primary key references auth.users(id) on delete cascade,
  full_name text not null check (char_length(trim(full_name)) >= 3),
  email text not null unique,
  role public.app_role not null default 'student',
  active boolean not null default true,
  privacy_consent_at timestamptz not null,
  privacy_policy_version text not null,
  created_at timestamptz not null default now()
);

create table public.courses (
  id uuid primary key default gen_random_uuid(),
  name text not null check (char_length(trim(name)) > 0),
  code text not null unique check (char_length(trim(code)) > 0),
  term text not null check (char_length(trim(term)) > 0),
  active boolean not null default true,
  created_by uuid not null references public.profiles(id),
  created_at timestamptz not null default now()
);

create table public.course_teachers (
  course_id uuid not null references public.courses(id) on delete cascade,
  teacher_id uuid not null references public.profiles(id) on delete cascade,
  primary key (course_id, teacher_id)
);

create table public.enrollments (
  course_id uuid not null references public.courses(id) on delete cascade,
  student_id uuid not null references public.profiles(id) on delete cascade,
  enrolled_at timestamptz not null default now(),
  primary key (course_id, student_id)
);

create table public.modules (
  id uuid primary key default gen_random_uuid(),
  title text not null check (char_length(trim(title)) > 0),
  description text not null default '',
  theory text not null default '',
  correct_example text not null default '',
  incorrect_example text not null default '',
  example_explanation text not null default '',
  position smallint not null check (position > 0),
  published boolean not null default false,
  created_by uuid not null references public.profiles(id),
  created_at timestamptz not null default now(),
  unique (position)
);

create table public.course_modules (
  course_id uuid not null references public.courses(id) on delete cascade,
  module_id uuid not null references public.modules(id) on delete cascade,
  primary key (course_id, module_id)
);

create table public.questions (
  id uuid primary key default gen_random_uuid(),
  module_id uuid not null references public.modules(id) on delete cascade,
  prompt text not null check (char_length(trim(prompt)) > 0),
  explanation text not null check (char_length(trim(explanation)) > 0),
  error_type text not null default 'general' check (char_length(trim(error_type)) > 0),
  difficulty smallint not null default 1 check (difficulty between 1 and 3),
  activity public.question_activity not null default 'both',
  position smallint not null check (position > 0),
  created_at timestamptz not null default now(),
  unique (module_id, position)
);

create table public.question_options (
  id uuid primary key default gen_random_uuid(),
  question_id uuid not null references public.questions(id) on delete cascade,
  option_text text not null check (char_length(trim(option_text)) > 0),
  is_correct boolean not null default false,
  position smallint not null check (position > 0),
  unique (question_id, position)
);
create unique index one_correct_option_per_question on public.question_options (question_id) where is_correct;

create table public.attempts (
  id uuid primary key default gen_random_uuid(),
  student_id uuid not null references public.profiles(id) on delete cascade,
  module_id uuid not null references public.modules(id) on delete restrict,
  status public.attempt_status not null default 'in_progress',
  score smallint check (score between 0 and 100),
  started_at timestamptz not null default now(),
  submitted_at timestamptz,
  check ((status = 'in_progress' and score is null and submitted_at is null) or
         (status = 'submitted' and score is not null and submitted_at is not null))
);
create index attempts_student_module_submitted_idx on public.attempts (student_id, module_id, submitted_at desc);

create table public.attempt_answers (
  attempt_id uuid not null references public.attempts(id) on delete cascade,
  question_id uuid not null references public.questions(id) on delete restrict,
  selected_option_id uuid references public.question_options(id) on delete set null,
  is_correct boolean not null,
  answered_at timestamptz not null default now(),
  primary key (attempt_id, question_id)
);

create table public.practice_answers (
  id uuid primary key default gen_random_uuid(),
  student_id uuid not null references public.profiles(id) on delete cascade,
  module_id uuid not null references public.modules(id) on delete restrict,
  question_id uuid not null references public.questions(id) on delete restrict,
  selected_option_id uuid not null references public.question_options(id) on delete restrict,
  is_correct boolean not null,
  answered_at timestamptz not null default now()
);
create index practice_answers_student_answered_idx on public.practice_answers (student_id, answered_at desc);

create table public.learning_resources (
  id uuid primary key default gen_random_uuid(),
  title text not null check (char_length(trim(title)) > 0),
  description text not null default '',
  url text not null check (url ~ '^https://'),
  category text not null default 'Académico',
  position smallint not null default 1 check (position > 0),
  published boolean not null default true,
  created_by uuid not null references public.profiles(id),
  created_at timestamptz not null default now(),
  unique (title)
);

create or replace function public.current_role()
returns public.app_role language sql stable security definer set search_path = public
as $$ select role from public.profiles where id = auth.uid() $$;

create or replace function public.is_admin()
returns boolean language sql stable security definer set search_path = public
as $$ select auth.uid() is not null and public.current_role() = 'admin' $$;

create or replace function public.is_course_teacher(target_course uuid)
returns boolean language sql stable security definer set search_path = public
as $$ select auth.uid() is not null and exists (select 1 from public.course_teachers where course_id = target_course and teacher_id = auth.uid()) $$;

create or replace function public.can_access_module(target_module uuid)
returns boolean language sql stable security definer set search_path = public
as $$
  select auth.uid() is not null and (
    public.current_role() in ('content_manager', 'admin') or exists (
      select 1 from public.course_modules cm
      left join public.enrollments e on e.course_id = cm.course_id and e.student_id = auth.uid()
      where cm.module_id = target_module and (e.student_id is not null or public.is_course_teacher(cm.course_id))
    )
  )
$$;

create or replace function public.create_profile_for_new_user()
returns trigger language plpgsql security definer set search_path = public
as $$
begin
  if coalesce((new.raw_user_meta_data ->> 'accepted_privacy_policy')::boolean, false) is not true then
    raise exception 'Debes aceptar la política de tratamiento de datos';
  end if;
  insert into public.profiles (id, full_name, email, privacy_consent_at, privacy_policy_version)
  values (
    new.id,
    coalesce(nullif(trim(new.raw_user_meta_data ->> 'full_name'), ''), 'Estudiante'),
    new.email,
    now(),
    coalesce(nullif(trim(new.raw_user_meta_data ->> 'privacy_policy_version'), ''), '2026-10-05')
  );
  return new;
end;
$$;
create trigger on_auth_user_created after insert on auth.users for each row execute procedure public.create_profile_for_new_user();

create or replace function public.start_attempt(target_module uuid)
returns public.attempts language plpgsql security definer set search_path = public
as $$
declare new_attempt public.attempts;
begin
  if auth.uid() is null or public.current_role() is distinct from 'student' then raise exception 'Solo estudiantes pueden iniciar evaluaciones'; end if;
  if not exists (select 1 from public.modules m where m.id = target_module and m.published and public.can_access_module(m.id)) then raise exception 'Módulo no disponible para tu curso'; end if;
  insert into public.attempts (student_id, module_id) values (auth.uid(), target_module) returning * into new_attempt;
  return new_attempt;
end;
$$;

create or replace function public.submit_attempt(target_attempt uuid, submitted_answers jsonb)
returns smallint language plpgsql security definer set search_path = public
as $$
declare attempt_row public.attempts;
declare total_questions integer;
declare correct_answers integer;
declare item jsonb;
declare selected_option uuid;
declare target_question uuid;
begin
  if jsonb_typeof(submitted_answers) <> 'array' then raise exception 'Formato de respuestas inválido'; end if;
  select * into attempt_row from public.attempts where id = target_attempt and student_id = auth.uid() and status = 'in_progress' for update;
  if not found then raise exception 'Intento no disponible'; end if;
  select count(*) into total_questions from public.questions where module_id = attempt_row.module_id and activity in ('evaluation', 'both');
  if total_questions = 0 or jsonb_array_length(submitted_answers) <> total_questions then raise exception 'Debes responder todas las preguntas'; end if;
  for item in select value from jsonb_array_elements(submitted_answers) loop
    target_question := (item ->> 'question_id')::uuid;
    selected_option := (item ->> 'option_id')::uuid;
    if not exists (select 1 from public.questions where id = target_question and module_id = attempt_row.module_id and activity in ('evaluation', 'both')) or not exists (select 1 from public.question_options where id = selected_option and question_id = target_question) then
      raise exception 'Respuesta inválida';
    end if;
    insert into public.attempt_answers (attempt_id, question_id, selected_option_id, is_correct)
    values (target_attempt, target_question, selected_option, (select is_correct from public.question_options where id = selected_option));
  end loop;
  select count(*) into correct_answers from public.attempt_answers where attempt_id = target_attempt and is_correct;
  update public.attempts set status = 'submitted', score = round(correct_answers::numeric * 100 / total_questions)::smallint, submitted_at = now() where id = target_attempt;
  return round(correct_answers::numeric * 100 / total_questions)::smallint;
end;
$$;

create or replace function public.check_practice_answer(target_question uuid, selected_option uuid)
returns table (is_correct boolean, explanation text, error_type text, difficulty smallint)
language plpgsql security definer set search_path = public
as $$
declare target_module uuid;
begin
  if auth.uid() is null or public.current_role() is distinct from 'student' then
    raise exception 'Solo estudiantes pueden resolver ejercicios';
  end if;
  select q.module_id into target_module
  from public.questions q
  where q.id = target_question and q.activity in ('practice', 'both');
  if target_module is null or not public.can_access_module(target_module) then
    raise exception 'Ejercicio no disponible';
  end if;
  if not exists (select 1 from public.question_options where id = selected_option and question_id = target_question) then
    raise exception 'Respuesta inválida';
  end if;
  insert into public.practice_answers (student_id, module_id, question_id, selected_option_id, is_correct)
  select auth.uid(), target_module, q.id, selected_option, o.is_correct
  from public.questions q join public.question_options o on o.id = selected_option
  where q.id = target_question;
  return query
  select o.is_correct, q.explanation, q.error_type, q.difficulty
  from public.questions q join public.question_options o on o.id = selected_option
  where q.id = target_question;
end;
$$;

create or replace function public.review_attempt(target_attempt uuid)
returns table (question_id uuid, is_correct boolean, explanation text)
language plpgsql security definer set search_path = public
as $$
begin
  if not exists (select 1 from public.attempts where id = target_attempt and student_id = auth.uid() and status = 'submitted') then
    raise exception 'Evaluación no disponible para revisión';
  end if;
  return query
  select aa.question_id, aa.is_correct, q.explanation
  from public.attempt_answers aa
  join public.questions q on q.id = aa.question_id
  where aa.attempt_id = target_attempt
  order by q.position;
end;
$$;

create or replace function public.student_progress()
returns table (evaluations_completed bigint, average_score numeric, practice_answered bigint, practice_correct bigint)
language sql stable security definer set search_path = public
as $$
  select
    (select count(*) from public.attempts where student_id = auth.uid() and status = 'submitted'),
    (select round(avg(score)::numeric, 1) from public.attempts where student_id = auth.uid() and status = 'submitted'),
    (select count(*) from public.practice_answers where student_id = auth.uid()),
    (select count(*) from public.practice_answers where student_id = auth.uid() and is_correct)
$$;

create or replace function public.teacher_results()
returns table (student_name text, student_email text, course_name text, module_title text, score smallint, submitted_at timestamptz)
language plpgsql stable security definer set search_path = public
as $$
begin
  if auth.uid() is null or public.current_role() not in ('teacher', 'admin') then raise exception 'No tienes permiso para consultar resultados'; end if;
  return query
  select distinct p.full_name, p.email, c.name, m.title, a.score, a.submitted_at
  from public.attempts a
  join public.profiles p on p.id = a.student_id
  join public.modules m on m.id = a.module_id
  join public.course_modules cm on cm.module_id = a.module_id
  join public.enrollments e on e.course_id = cm.course_id and e.student_id = a.student_id
  join public.courses c on c.id = cm.course_id
  where a.status = 'submitted' and (public.is_admin() or public.is_course_teacher(c.id))
  order by a.submitted_at desc;
end;
$$;

create or replace function public.teacher_error_summary()
returns table (module_title text, error_type text, answers_total bigint, errors_total bigint, error_rate numeric)
language plpgsql stable security definer set search_path = public
as $$
begin
  if auth.uid() is null or public.current_role() not in ('teacher', 'admin') then raise exception 'No tienes permiso para consultar errores'; end if;
  return query
  select m.title, q.error_type, count(aa.*), count(aa.*) filter (where not aa.is_correct),
    round(100.0 * count(aa.*) filter (where not aa.is_correct) / nullif(count(aa.*), 0), 1)
  from public.attempt_answers aa
  join public.attempts a on a.id = aa.attempt_id and a.status = 'submitted'
  join public.questions q on q.id = aa.question_id
  join public.modules m on m.id = q.module_id
  where public.is_admin() or exists (
    select 1 from public.course_modules cm
    join public.enrollments e on e.course_id = cm.course_id and e.student_id = a.student_id
    where cm.module_id = a.module_id and public.is_course_teacher(cm.course_id)
  )
  group by m.title, q.error_type
  order by errors_total desc, module_title;
end;
$$;

create or replace function public.admin_overview_stats()
returns table (active_users bigint, evaluations_completed bigint, success_rate numeric)
language plpgsql stable security definer set search_path = public
as $$
  select
    (select count(*) from public.profiles where active),
    (select count(*) from public.attempts where status = 'submitted'),
    coalesce((select round(100.0 * count(*) filter (where score >= 70) / nullif(count(*), 0), 1) from public.attempts where status = 'submitted'), 0)
  where public.is_admin()
$$;

create or replace function public.get_question_answer_key(target_question uuid)
returns table (id uuid, question_id uuid, option_text text, position smallint, is_correct boolean)
language plpgsql security definer set search_path = public
as $$
begin
  if auth.uid() is null or public.current_role() not in ('content_manager', 'admin') then raise exception 'No tienes permiso para consultar la clave'; end if;
  return query select o.id, o.question_id, o.option_text, o.position, o.is_correct from public.question_options o where o.question_id = target_question order by o.position;
end;
$$;

create or replace function public.save_question_with_options(target_question uuid, target_module uuid, question_prompt text, question_explanation text, target_error_type text, target_difficulty smallint, target_activity public.question_activity, choices jsonb)
returns uuid language plpgsql security definer set search_path = public
as $$
declare saved_question uuid; declare choice jsonb; declare choice_position integer := 0;
begin
  if auth.uid() is null or public.current_role() not in ('content_manager', 'admin') then raise exception 'No tienes permiso para editar ejercicios'; end if;
  if jsonb_typeof(choices) <> 'array' or jsonb_array_length(choices) <> 3 or char_length(trim(question_prompt)) = 0 or char_length(trim(question_explanation)) = 0 or char_length(trim(target_error_type)) = 0 or target_difficulty not between 1 and 3 then raise exception 'Datos del ejercicio inválidos'; end if;
  if (select count(*) from jsonb_array_elements(choices) value where coalesce((value ->> 'is_correct')::boolean, false)) <> 1 then raise exception 'Debe existir exactamente una alternativa correcta'; end if;
  if target_question is null then
    insert into public.questions (module_id, prompt, explanation, error_type, difficulty, activity, position) values (target_module, trim(question_prompt), trim(question_explanation), trim(target_error_type), target_difficulty, target_activity, coalesce((select max(position) + 1 from public.questions where module_id = target_module), 1)) returning id into saved_question;
  else
    update public.questions set module_id = target_module, prompt = trim(question_prompt), explanation = trim(question_explanation), error_type = trim(target_error_type), difficulty = target_difficulty, activity = target_activity where id = target_question returning id into saved_question;
    if saved_question is null then raise exception 'Ejercicio no disponible'; end if;
    delete from public.question_options where question_id = saved_question;
  end if;
  for choice in select value from jsonb_array_elements(choices) loop
    choice_position := choice_position + 1;
    insert into public.question_options (question_id, option_text, is_correct, position) values (saved_question, trim(choice ->> 'option_text'), (choice ->> 'is_correct')::boolean, choice_position);
  end loop;
  return saved_question;
end;
$$;

alter table public.profiles enable row level security;
alter table public.courses enable row level security;
alter table public.course_teachers enable row level security;
alter table public.enrollments enable row level security;
alter table public.modules enable row level security;
alter table public.course_modules enable row level security;
alter table public.questions enable row level security;
alter table public.question_options enable row level security;
alter table public.attempts enable row level security;
alter table public.attempt_answers enable row level security;
alter table public.practice_answers enable row level security;
alter table public.learning_resources enable row level security;

create policy "read permitted profiles" on public.profiles for select to authenticated using (id = auth.uid() or public.is_admin() or exists (select 1 from public.enrollments e where e.student_id = profiles.id and public.is_course_teacher(e.course_id)));
create policy "admins manage profiles" on public.profiles for all to authenticated using (public.is_admin()) with check (public.is_admin());
create policy "read assigned courses" on public.courses for select to authenticated using (public.is_admin() or public.is_course_teacher(id) or exists (select 1 from public.enrollments where course_id = courses.id and student_id = auth.uid()));
create policy "admins manage courses" on public.courses for all to authenticated using (public.is_admin()) with check (public.is_admin());
create policy "read teacher assignment" on public.course_teachers for select to authenticated using (teacher_id = auth.uid() or public.is_admin());
create policy "admins manage teacher assignment" on public.course_teachers for all to authenticated using (public.is_admin()) with check (public.is_admin());
create policy "read permitted enrollment" on public.enrollments for select to authenticated using (student_id = auth.uid() or public.is_admin() or public.is_course_teacher(course_id));
create policy "admins manage enrollments" on public.enrollments for all to authenticated using (public.is_admin()) with check (public.is_admin());
create policy "read assigned course modules" on public.course_modules for select to authenticated using (public.is_admin() or public.is_course_teacher(course_id) or exists (select 1 from public.enrollments where course_id = course_modules.course_id and student_id = auth.uid()));
create policy "admins manage course modules" on public.course_modules for all to authenticated using (public.is_admin()) with check (public.is_admin());
create policy "read available modules" on public.modules for select to authenticated using ((published and public.can_access_module(id)) or public.current_role() in ('content_manager', 'admin'));
create policy "content staff manage modules" on public.modules for all to authenticated using (public.current_role() in ('content_manager', 'admin')) with check (public.current_role() in ('content_manager', 'admin'));
create policy "read available questions" on public.questions for select to authenticated using (public.can_access_module(module_id));
create policy "content staff manage questions" on public.questions for all to authenticated using (public.current_role() in ('content_manager', 'admin')) with check (public.current_role() in ('content_manager', 'admin'));
create policy "read available options" on public.question_options for select to authenticated using (exists (select 1 from public.questions q where q.id = question_id and public.can_access_module(q.module_id)));
create policy "content staff manage options" on public.question_options for all to authenticated using (public.current_role() in ('content_manager', 'admin')) with check (public.current_role() in ('content_manager', 'admin'));
create policy "read permitted attempts" on public.attempts for select to authenticated using (student_id = auth.uid() or public.is_admin() or exists (select 1 from public.course_modules cm where cm.module_id = attempts.module_id and public.is_course_teacher(cm.course_id) and exists (select 1 from public.enrollments e where e.course_id = cm.course_id and e.student_id = attempts.student_id)));
create policy "read permitted answers" on public.attempt_answers for select to authenticated using (exists (select 1 from public.attempts a where a.id = attempt_id and (a.student_id = auth.uid() or public.is_admin() or exists (select 1 from public.course_modules cm where cm.module_id = a.module_id and public.is_course_teacher(cm.course_id) and exists (select 1 from public.enrollments e where e.course_id = cm.course_id and e.student_id = a.student_id)))));
create policy "read own practice answers" on public.practice_answers for select to authenticated using (student_id = auth.uid());
create policy "read published resources" on public.learning_resources for select to authenticated using (published or public.current_role() in ('content_manager', 'admin'));
create policy "content staff manage resources" on public.learning_resources for all to authenticated using (public.current_role() in ('content_manager', 'admin')) with check (public.current_role() in ('content_manager', 'admin'));

revoke all on all tables in schema public from anon;
revoke all on all tables in schema public from authenticated;
revoke all on all functions in schema public from public, anon;
grant usage on schema public to authenticated;
grant select on public.profiles, public.courses, public.course_teachers, public.enrollments, public.modules, public.course_modules, public.questions, public.attempts, public.attempt_answers, public.practice_answers, public.learning_resources to authenticated;
grant select (id, question_id, option_text, position), insert, update, delete on public.question_options to authenticated;
grant insert, update, delete on public.courses, public.course_teachers, public.enrollments, public.course_modules, public.modules, public.questions to authenticated;
grant update (role, active) on public.profiles to authenticated;
grant execute on function public.current_role(), public.is_admin(), public.is_course_teacher(uuid), public.can_access_module(uuid), public.start_attempt(uuid), public.submit_attempt(uuid, jsonb), public.check_practice_answer(uuid, uuid), public.review_attempt(uuid), public.student_progress(), public.teacher_results(), public.teacher_error_summary(), public.admin_overview_stats(), public.get_question_answer_key(uuid), public.save_question_with_options(uuid, uuid, text, text, text, smallint, public.question_activity, jsonb) to authenticated;
