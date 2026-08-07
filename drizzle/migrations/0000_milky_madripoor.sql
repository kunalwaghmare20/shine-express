CREATE TABLE `branches` (
	`id` varchar(36) NOT NULL,
	`company_id` varchar(36) NOT NULL,
	`name` varchar(255) NOT NULL,
	`code` varchar(50) NOT NULL,
	`email` varchar(255),
	`phone` varchar(20),
	`address` varchar(500) NOT NULL,
	`city` varchar(100) NOT NULL,
	`state` varchar(100) NOT NULL,
	`pincode` varchar(10) NOT NULL,
	`latitude` double,
	`longitude` double,
	`is_active` boolean NOT NULL DEFAULT true,
	`created_at` datetime(3) NOT NULL,
	`updated_at` datetime(3) NOT NULL,
	CONSTRAINT `branches_id` PRIMARY KEY(`id`),
	CONSTRAINT `branches_code_unique` UNIQUE(`code`)
);
--> statement-breakpoint
CREATE TABLE `companies` (
	`id` varchar(36) NOT NULL,
	`name` varchar(255) NOT NULL,
	`slug` varchar(255) NOT NULL,
	`logo` varchar(500),
	`gst_number` varchar(20),
	`pan_number` varchar(20),
	`email` varchar(255),
	`phone` varchar(20),
	`website` varchar(255),
	`address` varchar(500),
	`city` varchar(100),
	`state` varchar(100),
	`country` varchar(100) NOT NULL DEFAULT 'India',
	`pincode` varchar(10),
	`settings` json DEFAULT ('{}'),
	`is_active` boolean NOT NULL DEFAULT true,
	`created_at` datetime(3) NOT NULL,
	`updated_at` datetime(3) NOT NULL,
	CONSTRAINT `companies_id` PRIMARY KEY(`id`),
	CONSTRAINT `companies_slug_unique` UNIQUE(`slug`)
);
--> statement-breakpoint
CREATE TABLE `permissions` (
	`id` varchar(36) NOT NULL,
	`name` varchar(100) NOT NULL,
	`slug` varchar(100) NOT NULL,
	`description` varchar(500),
	`module` varchar(50) NOT NULL,
	`created_at` datetime(3) NOT NULL,
	CONSTRAINT `permissions_id` PRIMARY KEY(`id`),
	CONSTRAINT `permissions_slug_unique` UNIQUE(`slug`)
);
--> statement-breakpoint
CREATE TABLE `role_permissions` (
	`role_id` varchar(36) NOT NULL,
	`permission_id` varchar(36) NOT NULL,
	CONSTRAINT `role_permissions_role_id_permission_id_pk` PRIMARY KEY(`role_id`,`permission_id`)
);
--> statement-breakpoint
CREATE TABLE `roles` (
	`id` varchar(36) NOT NULL,
	`name` varchar(100) NOT NULL,
	`user_role` enum('SUPER_ADMIN','BRANCH_MANAGER','SERVICE_STAFF','CUSTOMER') NOT NULL,
	`description` varchar(500),
	`is_system` boolean NOT NULL DEFAULT false,
	`created_at` datetime(3) NOT NULL,
	`updated_at` datetime(3) NOT NULL,
	CONSTRAINT `roles_id` PRIMARY KEY(`id`),
	CONSTRAINT `roles_user_role_unique` UNIQUE(`user_role`)
);
--> statement-breakpoint
CREATE TABLE `addresses` (
	`id` varchar(36) NOT NULL,
	`customer_id` varchar(36) NOT NULL,
	`label` varchar(50) NOT NULL DEFAULT 'Home',
	`line1` varchar(255) NOT NULL,
	`line2` varchar(255),
	`city` varchar(100) NOT NULL,
	`state` varchar(100) NOT NULL,
	`pincode` varchar(10) NOT NULL,
	`country` varchar(100) NOT NULL DEFAULT 'India',
	`latitude` varchar(20),
	`longitude` varchar(20),
	`is_default` boolean NOT NULL DEFAULT false,
	`created_at` datetime(3) NOT NULL,
	`updated_at` datetime(3) NOT NULL,
	CONSTRAINT `addresses_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `customers` (
	`id` varchar(36) NOT NULL,
	`user_id` varchar(36) NOT NULL,
	`gst_number` varchar(20),
	`notes` varchar(2000),
	`deleted_at` datetime(3),
	`created_at` datetime(3) NOT NULL,
	`updated_at` datetime(3) NOT NULL,
	CONSTRAINT `customers_id` PRIMARY KEY(`id`),
	CONSTRAINT `customers_user_id_unique` UNIQUE(`user_id`)
);
--> statement-breakpoint
CREATE TABLE `users` (
	`id` varchar(36) NOT NULL,
	`clerk_id` varchar(255),
	`email` varchar(255) NOT NULL,
	`phone` varchar(20),
	`first_name` varchar(100) NOT NULL,
	`last_name` varchar(100) NOT NULL,
	`avatar_url` varchar(500),
	`user_role` enum('SUPER_ADMIN','BRANCH_MANAGER','SERVICE_STAFF','CUSTOMER') NOT NULL DEFAULT 'CUSTOMER',
	`is_active` boolean NOT NULL DEFAULT true,
	`last_login_at` datetime(3),
	`deleted_at` datetime(3),
	`created_at` datetime(3) NOT NULL,
	`updated_at` datetime(3) NOT NULL,
	CONSTRAINT `users_id` PRIMARY KEY(`id`),
	CONSTRAINT `users_clerk_id_unique` UNIQUE(`clerk_id`),
	CONSTRAINT `users_email_unique` UNIQUE(`email`),
	CONSTRAINT `roles_user_role_unique` UNIQUE(`user_role`)
);
--> statement-breakpoint
CREATE TABLE `attendance` (
	`id` varchar(36) NOT NULL,
	`employee_id` varchar(36) NOT NULL,
	`date` datetime NOT NULL,
	`check_in` datetime(3),
	`check_out` datetime(3),
	`attendance_status` enum('PRESENT','ABSENT','HALF_DAY','LEAVE') NOT NULL DEFAULT 'PRESENT',
	`notes` varchar(500),
	`latitude` varchar(20),
	`longitude` varchar(20),
	`created_at` datetime(3) NOT NULL,
	CONSTRAINT `attendance_id` PRIMARY KEY(`id`),
	CONSTRAINT `attendance_employee_date_idx` UNIQUE(`employee_id`,`date`)
);
--> statement-breakpoint
CREATE TABLE `documents` (
	`id` varchar(36) NOT NULL,
	`employee_id` varchar(36) NOT NULL,
	`document_type` enum('ID_PROOF','ADDRESS_PROOF','CONTRACT','CERTIFICATE','OTHER') NOT NULL,
	`name` varchar(255) NOT NULL,
	`url` varchar(500) NOT NULL,
	`uploaded_at` datetime(3) NOT NULL,
	CONSTRAINT `documents_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `employees` (
	`id` varchar(36) NOT NULL,
	`user_id` varchar(36) NOT NULL,
	`branch_id` varchar(36) NOT NULL,
	`employee_code` varchar(50) NOT NULL,
	`salary` decimal(12,2),
	`skills` json DEFAULT ('[]'),
	`availability` json DEFAULT ('{}'),
	`current_latitude` varchar(20),
	`current_longitude` varchar(20),
	`location_updated_at` datetime(3),
	`is_available` boolean NOT NULL DEFAULT true,
	`joined_at` datetime(3) NOT NULL,
	`deleted_at` datetime(3),
	`created_at` datetime(3) NOT NULL,
	`updated_at` datetime(3) NOT NULL,
	CONSTRAINT `employees_id` PRIMARY KEY(`id`),
	CONSTRAINT `employees_user_id_unique` UNIQUE(`user_id`),
	CONSTRAINT `employees_employee_code_unique` UNIQUE(`employee_code`)
);
--> statement-breakpoint
CREATE TABLE `service_categories` (
	`id` varchar(36) NOT NULL,
	`name` varchar(255) NOT NULL,
	`slug` varchar(255) NOT NULL,
	`description` varchar(1000),
	`icon` varchar(50),
	`sort_order` int NOT NULL DEFAULT 0,
	`is_active` boolean NOT NULL DEFAULT true,
	`created_at` datetime(3) NOT NULL,
	`updated_at` datetime(3) NOT NULL,
	CONSTRAINT `service_categories_id` PRIMARY KEY(`id`),
	CONSTRAINT `service_categories_slug_unique` UNIQUE(`slug`)
);
--> statement-breakpoint
CREATE TABLE `service_items` (
	`id` varchar(36) NOT NULL,
	`service_id` varchar(36) NOT NULL,
	`name` varchar(255) NOT NULL,
	`description` varchar(1000),
	`price` decimal(10,2) NOT NULL,
	`duration` int,
	`is_active` boolean NOT NULL DEFAULT true,
	`sort_order` int NOT NULL DEFAULT 0,
	`created_at` datetime(3) NOT NULL,
	`updated_at` datetime(3) NOT NULL,
	CONSTRAINT `service_items_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `services` (
	`id` varchar(36) NOT NULL,
	`category_id` varchar(36) NOT NULL,
	`name` varchar(255) NOT NULL,
	`slug` varchar(255) NOT NULL,
	`description` varchar(2000),
	`base_price` decimal(10,2) NOT NULL,
	`duration` int NOT NULL,
	`images` json DEFAULT ('[]'),
	`is_active` boolean NOT NULL DEFAULT true,
	`sort_order` int NOT NULL DEFAULT 0,
	`created_at` datetime(3) NOT NULL,
	`updated_at` datetime(3) NOT NULL,
	CONSTRAINT `services_id` PRIMARY KEY(`id`),
	CONSTRAINT `services_slug_unique` UNIQUE(`slug`)
);
--> statement-breakpoint
CREATE TABLE `booking_assignments` (
	`id` varchar(36) NOT NULL,
	`booking_id` varchar(36) NOT NULL,
	`employee_id` varchar(36) NOT NULL,
	`assigned_by_id` varchar(36),
	`is_primary` boolean NOT NULL DEFAULT true,
	`accepted_at` datetime(3),
	`rejected_at` datetime(3),
	`rejection_reason` varchar(500),
	`created_at` datetime(3) NOT NULL,
	CONSTRAINT `booking_assignments_id` PRIMARY KEY(`id`),
	CONSTRAINT `booking_assignments_booking_employee_idx` UNIQUE(`booking_id`,`employee_id`)
);
--> statement-breakpoint
CREATE TABLE `booking_items` (
	`id` varchar(36) NOT NULL,
	`booking_id` varchar(36) NOT NULL,
	`service_item_id` varchar(36),
	`name` varchar(255) NOT NULL,
	`description` varchar(1000),
	`price` decimal(10,2) NOT NULL,
	`quantity` int NOT NULL DEFAULT 1,
	CONSTRAINT `booking_items_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `booking_status_history` (
	`id` varchar(36) NOT NULL,
	`booking_id` varchar(36) NOT NULL,
	`booking_status` enum('PENDING','CONFIRMED','ASSIGNED','ACCEPTED','ON_THE_WAY','STARTED','COMPLETED','CANCELLED','REJECTED') NOT NULL DEFAULT 'PENDING',
	`changed_by_id` varchar(36),
	`notes` varchar(500),
	`created_at` datetime(3) NOT NULL,
	CONSTRAINT `booking_status_history_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `bookings` (
	`id` varchar(36) NOT NULL,
	`booking_number` varchar(50) NOT NULL,
	`customer_id` varchar(36) NOT NULL,
	`branch_id` varchar(36) NOT NULL,
	`service_id` varchar(36) NOT NULL,
	`address_id` varchar(36) NOT NULL,
	`booking_status` enum('PENDING','CONFIRMED','ASSIGNED','ACCEPTED','ON_THE_WAY','STARTED','COMPLETED','CANCELLED','REJECTED') NOT NULL DEFAULT 'PENDING',
	`scheduled_date` datetime NOT NULL,
	`scheduled_time` varchar(10) NOT NULL,
	`estimated_duration` int NOT NULL,
	`customer_notes` varchar(2000),
	`internal_notes` varchar(2000),
	`subtotal` decimal(12,2) NOT NULL DEFAULT '0',
	`tax_rate` decimal(5,2) NOT NULL DEFAULT '18',
	`tax_amount` decimal(12,2) NOT NULL DEFAULT '0',
	`discount` decimal(12,2) NOT NULL DEFAULT '0',
	`total_amount` decimal(12,2) NOT NULL DEFAULT '0',
	`assigned_at` datetime(3),
	`accepted_at` datetime(3),
	`started_at` datetime(3),
	`completed_at` datetime(3),
	`cancelled_at` datetime(3),
	`cancellation_reason` varchar(500),
	`created_at` datetime(3) NOT NULL,
	`updated_at` datetime(3) NOT NULL,
	CONSTRAINT `bookings_id` PRIMARY KEY(`id`),
	CONSTRAINT `bookings_booking_number_unique` UNIQUE(`booking_number`)
);
--> statement-breakpoint
CREATE TABLE `photos` (
	`id` varchar(36) NOT NULL,
	`booking_id` varchar(36) NOT NULL,
	`employee_id` varchar(36),
	`url` varchar(500) NOT NULL,
	`photo_type` enum('BEFORE','AFTER') NOT NULL,
	`caption` varchar(500),
	`created_at` datetime(3) NOT NULL,
	CONSTRAINT `photos_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `invoices` (
	`id` varchar(36) NOT NULL,
	`invoice_number` varchar(50) NOT NULL,
	`booking_id` varchar(36) NOT NULL,
	`customer_id` varchar(36) NOT NULL,
	`subtotal` decimal(12,2) NOT NULL,
	`tax_rate` decimal(5,2) NOT NULL,
	`tax_amount` decimal(12,2) NOT NULL,
	`cgst` decimal(12,2) NOT NULL DEFAULT '0',
	`sgst` decimal(12,2) NOT NULL DEFAULT '0',
	`igst` decimal(12,2) NOT NULL DEFAULT '0',
	`discount` decimal(12,2) NOT NULL DEFAULT '0',
	`total_amount` decimal(12,2) NOT NULL,
	`invoice_status` enum('DRAFT','ISSUED','PAID','OVERDUE','CANCELLED') NOT NULL DEFAULT 'DRAFT',
	`pdf_url` varchar(500),
	`issued_at` datetime(3),
	`due_date` datetime,
	`created_at` datetime(3) NOT NULL,
	`updated_at` datetime(3) NOT NULL,
	CONSTRAINT `invoices_id` PRIMARY KEY(`id`),
	CONSTRAINT `invoices_invoice_number_unique` UNIQUE(`invoice_number`),
	CONSTRAINT `invoices_booking_id_unique` UNIQUE(`booking_id`)
);
--> statement-breakpoint
CREATE TABLE `payments` (
	`id` varchar(36) NOT NULL,
	`booking_id` varchar(36) NOT NULL,
	`invoice_id` varchar(36),
	`customer_id` varchar(36) NOT NULL,
	`amount` decimal(12,2) NOT NULL,
	`payment_method` enum('CASH','UPI','CARD','RAZORPAY','STRIPE') NOT NULL,
	`payment_status` enum('PENDING','COMPLETED','FAILED','REFUNDED') NOT NULL DEFAULT 'PENDING',
	`transaction_id` varchar(255),
	`gateway_response` json,
	`paid_at` datetime(3),
	`created_at` datetime(3) NOT NULL,
	`updated_at` datetime(3) NOT NULL,
	CONSTRAINT `payments_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `audit_logs` (
	`id` varchar(36) NOT NULL,
	`user_id` varchar(36),
	`audit_action` enum('CREATE','UPDATE','DELETE','LOGIN','LOGOUT','STATUS_CHANGE','ASSIGN','PAYMENT') NOT NULL,
	`entity` varchar(100) NOT NULL,
	`entity_id` varchar(36) NOT NULL,
	`old_values` json,
	`new_values` json,
	`ip_address` varchar(45),
	`user_agent` varchar(500),
	`created_at` datetime(3) NOT NULL,
	CONSTRAINT `audit_logs_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `notifications` (
	`id` varchar(36) NOT NULL,
	`user_id` varchar(36) NOT NULL,
	`title` varchar(255) NOT NULL,
	`body` varchar(2000) NOT NULL,
	`notification_type` enum('BOOKING_CREATED','BOOKING_CONFIRMED','BOOKING_ASSIGNED','BOOKING_STARTED','BOOKING_COMPLETED','BOOKING_CANCELLED','PAYMENT_RECEIVED','INVOICE_GENERATED','REVIEW_REQUEST','GENERAL') NOT NULL DEFAULT 'GENERAL',
	`notification_channel` enum('IN_APP','EMAIL','SMS','WHATSAPP') NOT NULL DEFAULT 'IN_APP',
	`is_read` boolean NOT NULL DEFAULT false,
	`metadata` json DEFAULT ('{}'),
	`sent_at` datetime(3),
	`read_at` datetime(3),
	`created_at` datetime(3) NOT NULL,
	CONSTRAINT `notifications_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `reviews` (
	`id` varchar(36) NOT NULL,
	`booking_id` varchar(36) NOT NULL,
	`customer_id` varchar(36) NOT NULL,
	`employee_id` varchar(36),
	`rating` int NOT NULL,
	`comment` varchar(2000),
	`created_at` datetime(3) NOT NULL,
	CONSTRAINT `reviews_id` PRIMARY KEY(`id`),
	CONSTRAINT `reviews_booking_id_unique` UNIQUE(`booking_id`)
);
