# Classroom Attendance Service (Laravel)

A simple and secure PHP service to mark student attendance in classrooms.

## Technologyy Used

-   PHP 8.3
-   Laravel 12
-   MySQL 8
-   Redis
-   Sanctum

## Setup Instructions

### Clone the repository

clone this repository

The repository includes the docker-compose.yml file. If Docker and Docker Compose are installed on your system, you can run the project using either Sail or standard Docker commands.
Make sure your `.env` file is configured with a valid database connection, `.env.testing` for testing.

### Running with sail

```bash
./vendor/bin/sail up -d
```

### Migrations Commands

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

### Clear Redis

```bash
./vendor/bin/sail redis FLUSHALL
```

### Stopping container

```bash
./vendor/bin/sail down
```

### Accessing Bash

```bash
./vendor/bin/sail bash
```

### Running Queues

```bash
./vendor/bin/sail artisan queue:work
```

    or run below command  inside the bash

```bash
php artisan queue:work
```

### Running Tests

```bash
./vendor/bin/sail artisan test
```

    or run below command  inside the bash

```bash
php artisan test --env=testing
```

OR 


###  Install dependencies

```bash
composer install
```

### Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Make sure your `.env` file is configured with a valid database connection, `.env.testing` for testing.

### Run database migrations

```bash
php artisan migrate
```

### Start the server

```bash
php artisan serve
```

###  Run the queue

```bash
php artisan queue:work
```

###  Run the tests

```bash
php artisan test
```


## Structure

```

app/
├── Http/
│ ├── Controllers
│ │ ├── AttendanceController.php
│ │ ├── AuthController.php
│ ├──Jobs
│ │ ├── BulkMarkAttendanceJob.php
├── Models/
│ ├── Attendance.php
│ ├── Classroom.php
│ ├── Student.php
│ ├── User.php
├── Policies/
│ ├── AttendancePolicy.php
├── Providers/
│ ├── AppServiceProvider.php
├── Rules/
│ ├── StudentBelongsToClassroom.php
├── Services/
│ ├── SanctumAbilityChecker.php
│ └── Contracts/
│ ├── AbilityCheckerInterface.php
database/
├── factories(Factories files)
│ ├── ...
├──migrations
│ ├── ...(Migration files)
├──seeders
│ ├── ...(Seeder files)
routes/
├──api.php
tests/
└── Feature/
│ └── AttendanceStoreTest.php
│ └── AttendanceUpdateTest.php
│ └── BulkAttendanceTest.php
.env
.env.testing

```

## API Documentation

### Login

The user logs in and receives a bearer token with assigned abilities.

```http
POST /api/login
```

| Header         | Type     | Description                           |
| :------------- | :------- | :------------------------------------ |
| `Content-Type` | `string` | **Required**. Use `application/json`. |

| Parameter  | Type     | Description   |
| :--------- | :------- | :------------ |
| `email`    | `email`  | **Required**. |
| `password` | `string` | **Required**. |

---

### Mark Bulk Attendance

Bulk Attendance can only be marked using tokens that have the mark-attendance ability. The process is Asynchronous.

```http
POST /api/attendance/bulk
```

| Header          | Type     | Description                           |
| :-------------- | :------- | :------------------------------------ |
| `Authorization` | `string` | **Required**. Bearer token.           |
| `Content-Type`  | `string` | **Required**. Use `application/json`. |

| Parameter                   | Type      | Description   |
| :-------------------------- | :-------- | :------------ |
| `attendance`                | `array`   | **Required**. |
| `attendance[].student_id`   | `integer` | **Required**. |
| `attendance[].classroom_id` | `integer` | **Required**. |
| `attendance[].status`       | `string`  | **Required**. |
| `attendance[].marked_by`    | `integer` | **Required**. |
| `attendance[].remarks`      | `string`  | **Optional**. |

---

### Mark Single Attendance

Attendance can only be marked using tokens that have the mark-attendance ability. The process is synchronous.

```http
POST /api/attendance
```

| Header          | Type     | Description                           |
| :-------------- | :------- | :------------------------------------ |
| `Authorization` | `string` | **Required**. Bearer token.           |
| `Content-Type`  | `string` | **Required**. Use `application/json`. |

| Parameter      | Type      | Description   |
| :------------- | :-------- | :------------ |
| `classroom_id` | `integer` | **Required**. |
| `student_id`   | `integer` | **Required**. |
| `status`       | `string`  | **Required**. |
| `remarks`      | `string`  | **Optional**. |

---

### Get a Specific Attendance

Attendance can only viewed using tokens that have the view-attendance ability. The process is synchronous.

```http
GET /api/attendance/{id}
```

| Header          | Type     | Description                 |
| :-------------- | :------- | :-------------------------- |
| `Authorization` | `string` | **Required**. Bearer token. |

---

### Get Attendance of a Student

Attendance can only viewed using tokens that have the view-attendance ability. The process is synchronous.

```http
GET /api/students/{student_id}/attendance
```

| Header          | Type     | Description                 |
| :-------------- | :------- | :-------------------------- |
| `Authorization` | `string` | **Required**. Bearer token. |

---

### Update an Attendance

Attendance can only be updated by an Admin or the Teacher who originally marked it. The process is synchronous.

```http
PUT /api/posts/{post_id}
```

| Header          | Type     | Description                           |
| :-------------- | :------- | :------------------------------------ |
| `Authorization` | `string` | **Required**. Bearer token.           |
| `Content-Type`  | `string` | **Required**. Use `application/json`. |

| Parameter | Type     | Description   |
| :-------- | :------- | :------------ |
| `status`  | `string` | **Required**. |
| `remarks` | `string` | **Optional**. |

---

### Delete an Attendance

Attendance can only deleted using tokens that have the manage-attendance ability. The process is synchronous.

```http
DELETE /api/attendance/{id}
```

| Header          | Type     | Description                 |
| :-------------- | :------- | :-------------------------- |
| `Authorization` | `string` | **Required**. Bearer token. |

---

### Logout


```http
POST /api/logout/
```

| Header          | Type     | Description                 |
| :-------------- | :------- | :-------------------------- |
| `Authorization` | `string` | **Required**. Bearer token. |

---

## Assumptions & Clarifications
- Used Seeders to populate Users, Students, and Classrooms for testing purposes.
- Token Abilities are used for fine-grained API authorization.
- Laravel Sanctum handles API token-based authentication.
- Implemented a Queued Background Job (BulkMarkAttendanceJob) to handle bulk attendance marking asynchronously.
- No UI/frontend is implemented; the focus is entirely on clean, testable, and efficient RESTful APIs.
- Followed MVC architecture and service-based code organization where applicable.
- Used Validation, Policies, and Abilities to ensure secure access control.
- Indexes added to database tables where necessary to improve query performance.
- Applied Eager Loading with Field Selection to optimize API performance and reduce response size.
- Used PHPUnit for  testing.
- Rate limiting, error logging are considered.

