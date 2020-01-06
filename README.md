# linode-pmm
Scripts for test deployment of Percona Monitoring and Management (PMM) v2 on Linode

I use these scripts to be able to quickly deploy PMM Server and number of Database instances running test load for testing purposes.

To allow multiple deployments the same time the concept of "prefix" is used - instances which share prefix will be considered part of the same deployment.   Prefixes which are substring of each other are not supported - ie do not use "test" and "test2" instead use "test1" and "test2" prefixes

This set of scripts relies on the installed and configured Linode-CLI to control services in your account 

For controlling workloads on the specific nodes we use Linode StackScripts interface (for legacy purposes) 

## Environment Variables

The scripts use the following environment variables for configuration

LINODE_ROOT_PASSWORD  -  Root Password to set provitioning Linodes
PMM_PREFIX - Prefix to use for deployment 
PMM_ADMIN_PASSWORD - Server to set for PMM Server while provisioning it and to use while provisioning workload clients 
PMM_SERVER -  The IP/Host Name of the PMM Server.  Required by Scripts provisioning workloads for existing PMM Server Only 

## Scripts 

clean_prefix.php  - Deletes all linodes except PMM server matching given prefix.  Very helpful when deploying many test workload nodes.
start_pmm_only.php - Starts PMM Server (latest GA, but you can easily change it) 
start_tests.php  - Starts configured tests for PMM Server which is already provisioned. 



