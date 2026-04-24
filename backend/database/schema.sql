CREATE TYPE user_role AS ENUM ('USER', 'ADMIN');
CREATE TYPE quest_kind AS ENUM ('daily', 'weekly', 'progression', 'event');
CREATE TYPE user_quest_status AS ENUM ('in_progress', 'completed', 'expired');

CREATE TABLE users (
  id BIGSERIAL PRIMARY KEY,
  email VARCHAR(180) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  xp INT NOT NULL DEFAULT 0,
  role user_role NOT NULL DEFAULT 'USER',
  created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE quest_templates (
  id BIGSERIAL PRIMARY KEY,
  kind quest_kind NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  xp_reward INT NOT NULL CHECK (xp_reward >= 0),
  required_level INT NOT NULL DEFAULT 1 CHECK (required_level >= 1),
  created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE events (
  id BIGSERIAL PRIMARY KEY,
  created_by_user_id BIGINT NOT NULL REFERENCES users(id),
  starts_at TIMESTAMP NOT NULL,
  ends_at TIMESTAMP NOT NULL,
  xp_reward INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT NOW(),
  CHECK (ends_at > starts_at)
);

CREATE TABLE event_quest_selections (
  event_id BIGINT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
  quest_template_id BIGINT NOT NULL REFERENCES quest_templates(id),
  PRIMARY KEY (event_id, quest_template_id)
);

CREATE TABLE user_quests (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  quest_template_id BIGINT NOT NULL REFERENCES quest_templates(id),
  event_id BIGINT NULL REFERENCES events(id) ON DELETE SET NULL,
  status user_quest_status NOT NULL DEFAULT 'in_progress',
  is_validated BOOLEAN NOT NULL DEFAULT FALSE,
  started_at TIMESTAMP NOT NULL DEFAULT NOW(),
  completed_at TIMESTAMP NULL
);

CREATE UNIQUE INDEX uniq_user_quest_reward_once
ON user_quests (user_id, quest_template_id, event_id, status)
WHERE status = 'completed';

CREATE TABLE user_quest_action_logs (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  user_quest_id BIGINT NOT NULL REFERENCES user_quests(id) ON DELETE CASCADE,
  comment TEXT NOT NULL,
  action_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE notifications (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  title VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT NOW(),
  read_at TIMESTAMP NULL
);
