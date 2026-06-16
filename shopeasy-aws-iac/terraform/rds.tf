# DB subnet group using private database subnets
resource "aws_db_subnet_group" "shopeasy_db_subnet_group" {
  name = "${var.project_name}-db-subnet-group"

  subnet_ids = [
    aws_subnet.private_db_az1.id,
    aws_subnet.private_db_az2.id
  ]

  tags = {
    Name = "${var.project_name}-db-subnet-group"
  }
}

# Amazon RDS MySQL database for ShopEasy
resource "aws_db_instance" "shopeasy_rds" {
  identifier = "${var.project_name}-rds-mysql"

  engine         = "mysql"
  engine_version = "8.0"
  instance_class = "db.t3.micro"

  allocated_storage     = 20
  max_allocated_storage = 50
  storage_type          = "gp3"
  storage_encrypted     = true
  kms_key_id            = aws_kms_key.shopeasy_kms.arn

  db_name  = var.db_name
  username = var.db_username
  password = var.db_password

  db_subnet_group_name   = aws_db_subnet_group.shopeasy_db_subnet_group.name
  vpc_security_group_ids = [aws_security_group.rds_sg.id]

  publicly_accessible = false
  multi_az            = false

  backup_retention_period = 1
  skip_final_snapshot     = true
  deletion_protection     = false

  tags = {
    Name = "${var.project_name}-rds-mysql"
  }
}

# Secrets Manager secret for RDS credentials
resource "aws_secretsmanager_secret" "rds_credentials" {
  name        = "${var.project_name}-rds-credentials"
  description = "RDS credentials for ShopEasy application"
  kms_key_id  = aws_kms_key.shopeasy_kms.arn

  tags = {
    Name = "${var.project_name}-rds-credentials"
  }
}

# Store RDS connection details in Secrets Manager
resource "aws_secretsmanager_secret_version" "rds_credentials_version" {
  secret_id = aws_secretsmanager_secret.rds_credentials.id

  secret_string = jsonencode({
    username = var.db_username
    password = var.db_password
    engine   = "mysql"
    host     = aws_db_instance.shopeasy_rds.address
    port     = 3306
    dbname   = var.db_name
  })
}