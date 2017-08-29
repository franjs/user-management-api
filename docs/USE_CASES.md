Endpoints for Use Cases 
=======================

### As an admin I can add users:

- POST /api/users

### As an admin I can delete users:

- DELETE /api/users/{userId}

### As an admin I can assign users to a group they aren’t already part of:

- POST /api/users/{userId}/assign-to-group

### As an admin I can remove users from a group:

- POST /api/users/{userId}/remove-from-group

### As an admin I can create groups:

- POST /api/groups

### As an admin I can delete groups when they no longer have members:

- DELETE /api/groups/{groupId}

### To see other useful endpoints and learn how to use all of them go to:

- /api/doc on your web browser