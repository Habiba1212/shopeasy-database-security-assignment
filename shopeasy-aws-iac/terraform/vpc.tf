# Get available Availability Zones in the selected AWS region
data "aws_availability_zones" "available" {
  state = "available"
}

# VPC for ShopEasy
resource "aws_vpc" "shopeasy_vpc" {
  cidr_block           = "10.0.0.0/16"
  enable_dns_support   = true
  enable_dns_hostnames = true

  tags = {
    Name = "${var.project_name}-vpc"
  }
}

# Internet Gateway for public access to ALB
resource "aws_internet_gateway" "shopeasy_igw" {
  vpc_id = aws_vpc.shopeasy_vpc.id

  tags = {
    Name = "${var.project_name}-igw"
  }
}

# Public Subnet AZ1
resource "aws_subnet" "public_az1" {
  vpc_id                  = aws_vpc.shopeasy_vpc.id
  cidr_block              = "10.0.1.0/24"
  availability_zone       = data.aws_availability_zones.available.names[0]
  map_public_ip_on_launch = true

  tags = {
    Name = "${var.project_name}-public-subnet-az1"
  }
}

# Public Subnet AZ2
resource "aws_subnet" "public_az2" {
  vpc_id                  = aws_vpc.shopeasy_vpc.id
  cidr_block              = "10.0.2.0/24"
  availability_zone       = data.aws_availability_zones.available.names[1]
  map_public_ip_on_launch = true

  tags = {
    Name = "${var.project_name}-public-subnet-az2"
  }
}

# Private App Subnet AZ1
resource "aws_subnet" "private_app_az1" {
  vpc_id            = aws_vpc.shopeasy_vpc.id
  cidr_block        = "10.0.11.0/24"
  availability_zone = data.aws_availability_zones.available.names[0]

  tags = {
    Name = "${var.project_name}-private-app-subnet-az1"
  }
}

# Private App Subnet AZ2
resource "aws_subnet" "private_app_az2" {
  vpc_id            = aws_vpc.shopeasy_vpc.id
  cidr_block        = "10.0.12.0/24"
  availability_zone = data.aws_availability_zones.available.names[1]

  tags = {
    Name = "${var.project_name}-private-app-subnet-az2"
  }
}

# Private Database Subnet AZ1
resource "aws_subnet" "private_db_az1" {
  vpc_id            = aws_vpc.shopeasy_vpc.id
  cidr_block        = "10.0.21.0/24"
  availability_zone = data.aws_availability_zones.available.names[0]

  tags = {
    Name = "${var.project_name}-private-db-subnet-az1"
  }
}

# Private Database Subnet AZ2
resource "aws_subnet" "private_db_az2" {
  vpc_id            = aws_vpc.shopeasy_vpc.id
  cidr_block        = "10.0.22.0/24"
  availability_zone = data.aws_availability_zones.available.names[1]

  tags = {
    Name = "${var.project_name}-private-db-subnet-az2"
  }
}

# Public Route Table
resource "aws_route_table" "public_rt" {
  vpc_id = aws_vpc.shopeasy_vpc.id

  route {
    cidr_block = "0.0.0.0/0"
    gateway_id = aws_internet_gateway.shopeasy_igw.id
  }

  tags = {
    Name = "${var.project_name}-public-route-table"
  }
}

# Associate public subnets with public route table
resource "aws_route_table_association" "public_az1_assoc" {
  subnet_id      = aws_subnet.public_az1.id
  route_table_id = aws_route_table.public_rt.id
}

resource "aws_route_table_association" "public_az2_assoc" {
  subnet_id      = aws_subnet.public_az2.id
  route_table_id = aws_route_table.public_rt.id
}

# Private Route Table for app and DB subnets
resource "aws_route_table" "private_rt" {
  vpc_id = aws_vpc.shopeasy_vpc.id

  tags = {
    Name = "${var.project_name}-private-route-table"
  }
}

# Associate private app and DB subnets with private route table
resource "aws_route_table_association" "private_app_az1_assoc" {
  subnet_id      = aws_subnet.private_app_az1.id
  route_table_id = aws_route_table.private_rt.id
}

resource "aws_route_table_association" "private_app_az2_assoc" {
  subnet_id      = aws_subnet.private_app_az2.id
  route_table_id = aws_route_table.private_rt.id
}

resource "aws_route_table_association" "private_db_az1_assoc" {
  subnet_id      = aws_subnet.private_db_az1.id
  route_table_id = aws_route_table.private_rt.id
}

resource "aws_route_table_association" "private_db_az2_assoc" {
  subnet_id      = aws_subnet.private_db_az2.id
  route_table_id = aws_route_table.private_rt.id
}