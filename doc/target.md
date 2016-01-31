# Target

The goal of this system is be a data store with these main features:
 
 - easy to use ORM with types, attributes, and objects
 - uses and integrates with an existing relational database 
 - class generation
 - attributes may be part of multiple types
 - each set of change forms a commit in a branch
 - branches may be created and merged
 - an application can be told to use data at a previous point in history (any previous commit)
 - data is stored both as views (full tables) and as diffs (commits)
 - a commit may be reverted (made undone) if no later commits depend on it
 - the commit-history of an object, a type, and a user can be inspected     

## Concepts and tables

### Branch

A thread of commits, of which only the last one is saved.

- head-commit-id // the last commit of this branch

### Commit

A commit is a set of changes committed to the database.
A commit may be part of multiple branches. 

 - commit-id (string)
 - reason
 - username (string)
 - datetime
 
The commit-id is a numeric string that indicates the follow number inside the branch, and the mother branch.
I.e. 2.3: commit 3 of current branch, mother branch is 2 

### Commit object

An index into the commits, to find all objects of a given type that have changed in some span. 

 - commit-id
 - object-id
 - type-id
 - diff (longtext) 
 
The diff is an encoded structure of multiple changes. Examples:
 
 Change of attribute:
 
  +last-modified=1/1/2015;-last-modified=1/1/2014
  
 Change of type:
  
  +column=color

### View

A view is a flat representation of all objects of a type.

 - view-id
 - type-id

### View table

Table name: view-{view-id}

 - {type-specific-field}*

### Snapshot

A snapshot is a view that is linked to a commit

 - view-id
 - commit-d

### Branch view

A view that is updated each time the branch gets a new commit.
If a branch view matches a snapshot, that's incidental. Snapshot and branches are treated separately.

 - view-id
 - branch-id

## Processes

### A new commit

 - Create a commit
 - Create commit objects
 - Change the commit-id of the branch to the new commit
 - Is the branch-view used by other branches as well?
    - Yes: Create a clone of the active view, update this clone and update the branch-view
    - No: Update the view-table of the branch view with the new commit

### A new branch

 - Create a branch from the latest commit of the mother branch
 - Create branch-view objects for all types for this branch (note: no need to copy complete views)

### The type changes

 - Classes must be recreated
 - A special type change commit is created

### Merge

A merge from branch A to branch B goes as follows:

 - the common ancestor commit is found
 - all commits in between this common commit and the last commit of A are applied in a single commit to branch B
 
### Undo a commit

An undo of a commit is qua data just a new commit.

### Checkout of a commit (other than HEAD)

When a specific commit is checked out by a request, in fact a single snapshot is requested.

 - Does this snapshot exist?
    - No: created the view-table and the snapshot
    - Yes: no action

    