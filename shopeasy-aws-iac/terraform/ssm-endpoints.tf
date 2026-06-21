# Security Group for Systems Manager VPC Endpoints
resource "aws_security_group" "ssm_endpoint_sg" {
  name        = "${var.project_name}-ssm-endpoint-sg"
  description = "Allow EC2 instances to connect to SSM VPC endpoints"
  vpc_id      = aws_vpc.shopeasy_vpc.id

  ingress {
    description     = "Allow HTTPS from EC2 app servers to SSM endpoints"
    from_port       = 443
    to_port         = 443
    protocol        = "tcp"
    security_groups = [aws_security_group.ec2_sg.id]
  }

  egress {
    description = "Allow outbound HTTPS"
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "${var.project_name}-ssm-endpoint-sg"
  }
}

# VPC Endpoint for AWS Systems Manager
resource "aws_vpc_endpoint" "ssm" {
  vpc_id              = aws_vpc.shopeasy_vpc.id
  service_name        = "com.amazonaws.${var.aws_region}.ssm"
  vpc_endpoint_type   = "Interface"
  private_dns_enabled = true

  subnet_ids = [
    aws_subnet.private_app_az1.id,
    aws_subnet.private_app_az2.id
  ]

  security_group_ids = [
    aws_security_group.ssm_endpoint_sg.id
  ]

  tags = {
    Name = "${var.project_name}-ssm-endpoint"
  }
}

# VPC Endpoint for SSM Messages
resource "aws_vpc_endpoint" "ssmmessages" {
  vpc_id              = aws_vpc.shopeasy_vpc.id
  service_name        = "com.amazonaws.${var.aws_region}.ssmmessages"
  vpc_endpoint_type   = "Interface"
  private_dns_enabled = true

  subnet_ids = [
    aws_subnet.private_app_az1.id,
    aws_subnet.private_app_az2.id
  ]

  security_group_ids = [
    aws_security_group.ssm_endpoint_sg.id
  ]

  tags = {
    Name = "${var.project_name}-ssmmessages-endpoint"
  }
}

# VPC Endpoint for EC2 Messages
resource "aws_vpc_endpoint" "ec2messages" {
  vpc_id              = aws_vpc.shopeasy_vpc.id
  service_name        = "com.amazonaws.${var.aws_region}.ec2messages"
  vpc_endpoint_type   = "Interface"
  private_dns_enabled = true

  subnet_ids = [
    aws_subnet.private_app_az1.id,
    aws_subnet.private_app_az2.id
  ]

  security_group_ids = [
    aws_security_group.ssm_endpoint_sg.id
  ]

  tags = {
    Name = "${var.project_name}-ec2messages-endpoint"
  }
}